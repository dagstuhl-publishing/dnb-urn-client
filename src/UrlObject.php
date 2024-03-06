<?php


namespace Dagstuhl\DnbUrnClient;


class UrlObject
{
    private string $url;
    private ?string $created = NULL;
    private ?string $lastModified = NULL;
    private ?string $urn = NULL;
    private ?string $owner = NULL;
    private ?int $priority = NULL;
    private ?string $self = NULL;

    /**
     * UrlObject constructor.
     */
    public function __construct(string|object $url, int $priority = NULL)
    {
        if (is_object($url)) {
            // handles api-response url-object
            $this->url = !isset($url->url) ?: $url->url;
            $this->created = !isset($url->created) ?: $url->created;
            $this->lastModified = !isset($url->urn) ?: $url->lastModified;
            $this->owner = !isset($url->owner) ?: $url->owner;
            $this->priority = !isset($url->priority) ?: $url->priority;
            $this->self = !isset($url->self) ?: $url->self;
        }
        else {
            // handles string
            $this->url = $url;
        }

        if ($priority !== NULL) {
            $this->priority = $priority;
        }
    }

    /**
     * returns an instance of UrlObject, no matter if given an urlObject-instance, a string, or an array
     *
     * @param string|array $url
     * @return UrlObject|null
     */
    public static function create(string|array|self $url): ?static
    {
        if ($url instanceof self) {
            return $url;
        }
        elseif (!is_array($url)) {
            return new static($url);
        }
        elseif (isset($url['url'])) {
            return new static($url['url'], $url['priority'] ?? NULL);
        }
        elseif (count($url) === 1) {
            return new static($url[0]);
        }

        return NULL;
    }

    /**
     * returns an array of urlObjects while handling the following
     * 1) string: 'http..'
     * 2) string-array: [ 'http...' ]
     * 3) multiple string-array: [ 'http1...', http2...' ]
     * 4) string-url-array: [ 'url' => 'http...' ]
     * 5) url/prio-array: [ 'url' => 'http...', 'priority' => 1 ]
     * 6) multiple url/(prio)-arrays: [[ 'url' => 'http1...', 'priority' => 1 ], ['url' => 'http2...', 'priority' => 2 ], [ 'url' => 'http3...' ]]
     * 7) 1 urlObj
     * 8) 1 urlObj-array
     * 9) multiple urlObjs-array
     *
     * @param string|array|UrlObject $urlOrUrls
     * @return UrlObject|UrlObject[]
     */
    public static function createMany(string|array|self $urlOrUrls): UrlObject|array
    {
        $urlObjs = [];

        $urls = (!is_array($urlOrUrls) OR isset($urlOrUrls['url']))
            ? [ $urlOrUrls ]
            : $urlOrUrls;

        foreach ($urls as $url) {
            $urlObjs[] = self::create($url);
        }

        return $urlObjs;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $str): void
    {
        $this->url = $str;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function getLastModified(): ?string
    {
        return $this->lastModified;
    }

    public function getUrn(): ?string
    {
        return $this->urn;
    }

    public function setUrn(string $str): void
    {
        $this->urn = $str;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $int): void
    {
        $this->priority = $int;
    }

    public function getSelf(): ?string
    {
        return $this->self;
    }

    public function getApiData(bool $onlyPriority = false): array
    {
        if ($onlyPriority) {
            return [ 'priority' => $this->priority ];
        }

        return [ 'url' => $this->url, 'priority' => $this->priority ];
    }

    public function toJson(): string
    {
        return json_encode([
            'url' => $this->url,
            'created' => $this->created,
            'lastModified' => $this->lastModified,
            'urn' => $this->urn,
            'owner' => $this->owner,
            'priority' => $this->priority,
            'self' => $this->self
        ]);
    }
}