<?php


namespace LZI\DnbUrnClient;


class UrlObject
{
    private $url;
    private $created;
    private $lastModified;
    private $urn;
    private $owner;
    private $priority;
    private $self;

    /**
     * UrlObject constructor.
     * @param string $url
     * @param integer|null $priority
     */
    public function __construct($url, int $priority = NULL)
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

        $this->priority = $priority;
    }

    /**
     * returns an instance of UrlObject, no matter if given an urlObject-instance, a string, or an array
     *
     * @param string|array $url
     * @return UrlObject|null
     */
    public static function create($url)
    {
        if ($url instanceof self) {
            return $url;
        }
        elseif (!is_array($url)) {
            return new static($url);
        }
        elseif (isset($url['url'])) {
            $priority = (isset($url['priority']) ? $url['priority'] : NULL);
            return new static($url['url'], $priority);
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
    public static function createMany($urlOrUrls)
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

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $str
     */
    public function setUrl($str)
    {
        $this->url = $str;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getLastModified()
    {
        return $this->lastModified;
    }

    public function getUrn()
    {
        return $this->urn;
    }

    /**
     * @param $str
     */
    public function setUrn($str)
    {
        $this->urn = $str;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param integer $int
     */
    public function setPriority($int)
    {
        $this->priority = $int;
    }

    public function getSelf()
    {
        return $this->self;
    }

    /**
     * @param bool $onlyPrio
     * @return array
     */
    public function getApiData($onlyPrio = false) : array
    {
        if ($onlyPrio) {
            return [ 'priority' => $this->priority ];
        }

        return [ 'url' => $this->url, 'priority' => $this->priority ];
    }

    public function toJson()
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