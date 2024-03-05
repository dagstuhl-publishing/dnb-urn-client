<?php


namespace LZI\DnbUrnClient;


use stdClass;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

/**
 * Class UrnClient
 * @package App\Modules\Urn
 *
 * for reference, see https://wiki.dnb.de/display/URNSERVDOK/URN-Service+API
 *
 */
class UrnClient
{
    const ERROR_PREFIX = '!! ERROR ';

    private string $username;

    private string $password;

    private string $apiUrlBase;

    private string $endpoint;

    private HttpClientInterface $httpClient;

    private ?ResponseInterface $response = NULL;

    private ?int $status = NULL;

    private ?Throwable $exception = NULL;

    public function __construct($username = NULL, $password = NULL, $apiUrl = NULL)
    {
        $this->username = $username ?? config('urnclient.username');
        $this->password = $password ?? config('urnclient.password');
        $this->apiUrlBase = $apiUrlBase ?? config('urnclient.api-url');

        $this->httpClient = HttpClient::create();
    }

    public function getApiUrl(): string
    {
        return $this->apiUrlBase;
    }

    public function setEndpoint(string $url = ''): void
    {
        $this->endpoint = $this->apiUrlBase . $url;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function lastRequestFailed(): bool
    {
        return empty($this->status) OR $this->status < 200 OR $this->status >= 300;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getStatus(): ?int
    {
        $statusCode = NULL;

        if ($this->response instanceof ResponseInterface) {
            try {
                $statusCode = $this->response->getStatusCode();
            } catch (Throwable $ex) { }
        }

        return $statusCode;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function getErrorMessage(): string
    {
        if ($this->exception !== NULL) {
            return self::ERROR_PREFIX. $this->exception->getMessage();
        }

        return $this->lastRequestFailed()
            ? self::ERROR_PREFIX. 'UrnClient - Status of last response: '.$this->status
            : '';
    }

    private function getResponseBody(): ?stdClass
    {
        $responseBody = NULL;

        if ($this->response instanceof ResponseInterface) {
            try {
                $responseBody = json_decode($this->response->getContent());
            }
            catch(Throwable $ex) { }
        }

        return $this->lastRequestFailed()
            ? NULL
            : $responseBody;
    }

    private function makeRequest(...$requestParams): ?stdClass
    {
        $data = NULL;

        try {
            $this->exception = NULL;
            $this->status = NULL;
            $this->response = NULL;
            $this->response = $this->httpClient->request(...$requestParams);
            $this->status = $this->response->getStatusCode();

            $data = $this->getResponseBody();
        }
        catch(TransportExceptionInterface $ex) {
            $this->response = NULL;
            $this->exception = $ex;
        }

        return $data;
    }

    public static function getFormattedUrls(string|array $urlOrUrls): array
    {
        $formattedUrls = [];

        $urls = !is_array($urlOrUrls)
            ? [ $urlOrUrls ]
            : $urlOrUrls;

        foreach ($urls as $url) {

            if (!is_array($url)) {
                $formattedUrls[] = [ 'url' => $url ];
            }
            elseif (isset($url['url'])) {
                $formattedUrls[] = $url;
            }
        }

        return $formattedUrls;
    }


    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-URNabfragen
     */
    public function getUrnDetails(string $urn): ?stdClass
    {
        $this->setEndpoint('urns/urn/' . $urn);

        $data = $this->makeRequest(
            'GET',
            $this->endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
        ]);

        return ($this->lastRequestFailed() OR $data === NULL)
            ? NULL
            : $data;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-URLsaneinerURNabfragen
     *
     * @return UrlObject[]|null
     */
    public function getUrls(string $urn, bool $onlyOwn = false): ?array
    {
        $this->setEndpoint( $onlyOwn ? 'urns/urn/'. $urn .'/my-urls' : 'urns/urn/' . $urn .'/urls' );

        $data = $this->makeRequest(
            'GET',
            $this->endpoint, [
                'auth_basic' => [ $this->username, $this->password ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
        ]);

        if (!$this->lastRequestFailed() AND $data !== NULL) {

            $urls = [];

            foreach ($data->items as $item) {
                $urls[] = new UrlObject($item);
            }

            return $urls;
        }

        return NULL;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-EigeneURLsaneinerURNabfragen
     *
     * @return UrlObject[]|null
     */
    public function getOwnUrls(string $urn): ?array
    {
        return $this->getUrls($urn, true);
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-EinzelneURLabfragen
     *
     */
    public function getUrlDetails(string $urn, UrlObject|string|array $url): ?UrlObject
    {
        $url = UrlObject::create($url);
        $this->setEndpoint('urns/urn/'. $urn .'/urls/base64/'. base64_encode($url->getUrl()));

        $data = $this->makeRequest(
            'GET',
            $this->endpoint, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return ($this->lastRequestFailed() OR $data === NULL)
            ? NULL
            : new UrlObject($data);
    }

    public function urlExists(string $urn, UrlObject|string $url): bool
    {
        $urlDetails = $this->getUrlDetails($urn, $url);

        if ($urlDetails !== NULL AND $urlDetails instanceof UrlObject) {
            return true;
        }

        return false;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-HinzufügeneinerURLzueinerURN
     */
    public function addUrl(string $urn, string|array|UrlObject $url): ?UrlObject
    {
        $this->setEndpoint('urns/urn/'. $urn .'/urls');

        $urlObj = UrlObject::create($url);

        $data = $this->makeRequest(
            'POST',
            $this->endpoint, [
            'auth_basic' => [ $this->username, $this->password ],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => $urlObj->getApiData()
        ]);

        return ($this->lastRequestFailed() OR $data === NULL)
            ? NULL
            : new UrlObject($data); // returns urn-api-object
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-LöscheneinerURLvoneinerURN
     */
    public function deleteUrl(string $urn, UrlObject|string|array $url): bool|NULL
    {
        $urlObj = UrlObject::create($url);
        $this->setEndpoint('urns/urn/'. $urn .'/urls/base64/'. base64_encode($urlObj->getUrl()));

        $data = $this->makeRequest(
            'DELETE',
            $this->endpoint, [
            'auth_basic' => [ $this->username, $this->password ],
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        // response body of this request is always empty, thus send "true" if status == 204
        if ($this->status === 204) {
            return true;
        }

        return NULL;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-AustauschenallereigenenURLsaneinerURN
     */
    public function exchangeOwnUrls(string $urn, string|UrlObject|array $urlOrUrls): stdClass|bool|NULL
    {
        $this->setEndpoint('urns/urn/' . $urn . '/my-urls');

        $urls = [];

        foreach (UrlObject::createMany($urlOrUrls) as $urlObj) {
            $urls[] = $urlObj->getApiData();
        }

        $data = $this->makeRequest(
            'PATCH',
            $this->endpoint, [
            'auth_basic' => [ $this->username, $this->password ],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => $urls
        ]);

        // response body of this request is always empty, thus send "true" if not failed
        return ($this->lastRequestFailed() OR $data === NULL)
            ? ($this->status >= 300 ? NULL : true)
            : $data;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-AbfrageneinesVorschlagsfüreineURN
     *
     * return is of form: { "namespace": (string), "self": (string), "suggestedUrn": (string) }
     */
    public function getUrnSuggestion(string $namespace): ?stdClass
    {
        $this->setEndpoint('namespaces/name/'. $namespace .'/urn-suggestion');

        $data = $this->makeRequest(
            'GET',
            $this->endpoint, [
                'auth_basic' => [ $this->username, $this->password ],
                'headers' => [
                    'Accept' => 'application/json'
                ]
        ]);

        return ($this->lastRequestFailed() OR $data === NULL)
            ? NULL
            : $data;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-ExistenzprüfungfüreineURN
     *
     * @param string $urn
     * @return bool|null
     */
    public function urnExists(string $urn): ?bool
    {
        $this->setEndpoint('urns/urn/' . $urn);

        $this->makeRequest(
            'HEAD',
            $this->endpoint, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if ($this->status === 200) {
            return true;
        }
        elseif ($this->status === 404) {
            return false;
        }

        return NULL;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-RegistriereneinerneuenURN
     */
    public function registerUrn(string $urn, string|array|UrlObject $urlOrUrls): ?stdClass
    {
        $this->setEndpoint('urns');

        $urls = [];

        foreach (UrlObject::createMany($urlOrUrls) as $urlObj) {
            $urls[] = $urlObj->getApiData();
        }

        $data = $this->makeRequest(
            'POST',
            $this->endpoint, [
            'auth_basic' => [ $this->username, $this->password ],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => [
                'urn' => $urn,
                'urls' => $urls
            ]
        ]);

        return ($this->lastRequestFailed() OR $data === NULL)
            ? NULL // returns status code 409 if specified url is registered already to any urn
            : $data; // returns urn-api-object
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-AktualisierenderPrioritäteinerURL
     */
    public function updatePriority(string $urn, string|array|UrlObject $url, int $priority): ?bool
    {
        $urlObj = UrlObject::create($url);
        $urlObj->setPriority($priority);
        $this->setEndpoint('urns/urn/'. $urn .'/urls/base64/'. base64_encode($urlObj->getUrl()));

        $data = $this->makeRequest(
            'PATCH',
            $this->endpoint, [
            'auth_basic' => [ $this->username, $this->password ],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => $urlObj->getApiData(true)
        ]);

        // response body of this request is always empty, thus send "true" if status == 204
        if ($this->status === 204) {
            return true;
        }

        return NULL;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-NachfolgereinerURNsetzen
     */
    public function setUrnSuccessor(string $oldUrn, string $newUrn): ?bool
    {
        // TODO: check if function works properly
        $this->setEndpoint('urns/urn/'. $oldUrn);

        if (!strpos($newUrn, 'http') !== false) {
            $newCanonicalUrn = $this->getUrnDetails($newUrn)->self;
        }
        else {
            $newCanonicalUrn = $newUrn;
        }

        $data = $this->makeRequest(
            'PATCH',
            $this->endpoint, [
            'auth_basic' => [ $this->username, $this->password ],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => [ 'successor' => $newCanonicalUrn ]
        ]);

        // response body of this request is always empty, thus send "true" if status == 204
        if ($this->status === 204) {
            return true;
        }

        return NULL;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-NachfolgereinerURNentfernen
     */
    public function deleteUrnSuccessor(string $originalUrn): ?bool
    {
        // TODO: check if function works properly
        $this->setEndpoint('urns/urn/'. $originalUrn);

        $data = $this->makeRequest(
            'PATCH',
            $this->endpoint, [
            'auth_basic' => [ $this->username, $this->password ],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => [ 'successor' => null ]
        ]);

        // response body of this request is always empty, thus send "true" if status == 204
        if ($this->status === 204) {
            return true;
        }

        return NULL;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-InformationzuNamensraumanzeigen
     */
    public function getNamespaceDetails(string $namespace): ?stdClass
    {
        $this->setEndpoint('namespaces/name/'. $namespace);

        $data = $this->makeRequest(
            'GET',
            $this->endpoint, [
            'auth_basic' => [ $this->username, $this->password ],
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        return ($this->lastRequestFailed() OR $data === NULL)
            ? NULL
            : $data; // returns namespace-api-object
    }
}