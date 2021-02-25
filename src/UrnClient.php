<?php


namespace LZI\DnbUrnClient;


use Psr\Log\NullLogger;
use stdClass;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
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

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $apiUrlBase;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var HttpClientInterface|NULL
     */
    private $httpClient;

    /**
     * @var ResponseInterface|null
     */
    private $response = NULL;

    /**
     * @var int|null
     */
    private $status = NULL;

    /**
     * @var Throwable|null
     */
    private $exception = NULL;

    public function __construct($username = NULL, $password = NULL, $apiUrl = NULL)
    {
        $this->username = $username ?? config('urnclient.username');
        $this->password = $password ?? config('urnclient.password');
        $this->apiUrlBase = $apiUrlBase ?? config('urnclient.api-url');

        $this->httpClient = HttpClient::create();
    }

    /**
     * @param string $url
     */
    public function setEndpoint($url = '')
    {
        $this->endpoint = $this->apiUrlBase . $url;
    }

    /**
     * @return bool
     */
    public function lastRequestFailed()
    {
        return !($this->status >= 200 OR $this->status < 300);
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return int|null
     */
    public function getStatus()
    {
        $statusCode = NULL;

        if ($this->response instanceof ResponseInterface) {

            try {
                $statusCode = $this->response->getStatusCode();
            } catch (Throwable $ex) { }
        }

        return $statusCode;
    }

    /**
     * @return Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->exception !== NULL) {
            return self::ERROR_PREFIX. $this->exception->getMessage();
        }

        return $this->lastRequestFailed()
            ? self::ERROR_PREFIX. 'UrnClient - Status of last response: '.$this->status
            : '';
    }

    /**
     * @return stdClass|null
     */
    private function getResponseBody()
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

    /**
     * @param mixed ...$requestParams
     * @return stdClass|null
     */
    private function makeRequest(...$requestParams)
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


    /**
     * @param string|array $urlOrUrls
     * @return array
     */
    public static function getFormattedUrls($urlOrUrls) : array
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
     *
     * @param string $urn
     * @return stdClass|null
     */
    public function getUrnDetails($urn): ?stdClass
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
     * @param string $urn
     * @return UrlObject[]|null
     */
    public function getUrls(string $urn, $onlyOwn = false): ?array
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
     * @param string $urn
     * @return UrlObject[]|null
     */
    public function getOwnUrls(string $urn): ?array
    {
        return $this->getUrls($urn, true);
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-EinzelneURLabfragen
     *
     * @param string $urn
     * @param UrlObject|string|array $url
     * @return UrlObject|null
     */
    public function getUrlDetails(string $urn, $url): ?UrlObject
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

    /**
     * @param string $urn
     * @param UrlObject|string $url
     * @return bool
     */
    public function urlExists(string $urn, $url) : bool
    {
        $urlDetails = $this->getUrlDetails($urn, $url);

        if ($urlDetails !== NULL AND $urlDetails instanceof UrlObject) {
            return true;
        }

        return false;
    }

    /**
     * reference: https://wiki.dnb.de/display/URNSERVDOK/Beispiele%3A+URN-Verwaltung#Beispiele:URN-Verwaltung-HinzufügeneinerURLzueinerURN
     *
     * @param string $urn
     * @param string|array|UrlObject $url
     * @return UrlObject|null
     */
    public function addUrl(string $urn, $url): ?UrlObject
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
     *
     * @param string $urn
     * @param UrlObject|string|array $url
     * @return bool|stdClass|null
     */
    public function deleteUrl(string $urn, $url)
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
     *
     * @param string $urn
     * @param string|UrlObject|array $urlOrUrls
     * @return stdClass|bool|null
     */
    public function exchangeOwnUrls(string $urn, $urlOrUrls)
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
     *
     * @param string $namespace
     * @return stdClass|null
     */
    public function getUrnSuggestion($namespace): ?stdClass
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
     *
     * @param string $urn
     * @param string|array|UrlObject $urlOrUrls
     * @return stdClass|null
     */
    public function registerUrn($urn, $urlOrUrls): ?stdClass
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
     *
     * @param string $urn
     * @param string|array|UrlObject $url
     * @param int $priority
     * @return bool|null
     */
    public function updatePriority(string $urn, $url, int $priority): ?bool
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
     *
     * @param string $oldUrn
     * @param string $newUrn
     * @return bool|null
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
     *
     * @param string $originalUrn
     * @return bool|null
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
     *
     * @param string|null $namespace
     * @return stdClass|null
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