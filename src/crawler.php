<?php

/**
 * Created by PhpStorm.
 * User: gaoyichuan
 * Date: 2/7/17
 * Time: 1:39 PM
 */

class Crawler
{
    /** @var string */
    private $url;
    private $host;
    private $method = 'GET';
    private $payload = '';

    /** @var bool */
    private $sameHost = false;

    /**
     * @param string $url
     */
    public function setUrl(string $url) {
        $this->url = $url;
        $this->host = $this->getHostFromUrl($url);
    }

    /**
     * @param bool $sameHost
     */
    public function setSameHost(bool $sameHost) {
        $this->sameHost = $sameHost;
    }

    /**
     * @param string $method
     * @throws Exception
     */
    public function setMethod(string $method) {
        if(!in_array(strtoupper($method), array('POST','GET'))) {
            throw new Exception('Unknown method');
        }
        $this->method = $method;
    }

    /**
     * @param string $payload
     */
    public function setPayload(string $payload) {
        $this->payload = $payload;
    }

    public function __construct($url = '', bool $sameHost = false) {
        $this->setUrl($url);
        $this->setSameHost($sameHost);
    }

    public function crawl() {
        $seen = array();
        $res = array();

        try {
            $links = $this->fetchLinks($this->url, $this->method, $this->payload);
            foreach ($links as $link) {
                if (!array_key_exists($link['url'], $seen)) {
                    $seen[$link['url']] = true;
                    if ($this->sameHost) {
                        if ($this->host == $this->getHostFromUrl($link['url'])) $res[] = $link;
                    } else {
                        $res[] = $link;
                    }
                }
            }
            return $res;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param string $url the url to fetch
     * @param string $method post or get
     * @param string $payload payload for post
     * @throws Exception
     * @return array
     */
    private function fetchLinks($url, $method = 'GET', $payload = '') {
        if (!in_array(strtoupper($method), array('POST', 'GET'))) {
            throw new Exception('Unknown method');
        }

        if (!function_exists('curl_version')) {
            throw new Exception('Unable to find cURL extension');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if(strtoupper($method) == 'POST'){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $output = curl_exec($ch);
        curl_close($ch);

        if($output !== false){
            if(!class_exists('DOMDocument')){
                throw new Exception('Unable to find the DOM extension');
            }

            $res = array();
            $dom = new DOMDocument;
            @$dom->loadHTML($output);

            foreach ($dom->getElementsByTagName('a') as $node)
            {
                if($node->getAttribute('href') != ''){
                    $href = $node->getAttribute('href');

                    $prefix = '';
                    if(strpos($href, '/') == 0) {
                        $prefix = $this->getSchemeFromUrl($url) . '://' . $this->getHostFromUrl($url);
                    } elseif (strpos($href, 'http') != 0) {
                        $prefix = $url;
                    }

                    $href = $prefix . $href;

                    $res[] = array(
                        'title' => $node->nodeValue,
                        'url' => $href,
                    );
                }
            }
            return $res;
        } else {
            throw new Exception(curl_error($ch));
        }
    }

    /**
     * @param string $url
     * @return mixed host
     */
    private function getHostFromUrl($url){
        return parse_url($url, PHP_URL_HOST);
    }

    /**
     * @param string $url
     * @return mixed host
     */
    private function getSchemeFromUrl($url){
        return parse_url($url, PHP_URL_SCHEME);
    }
}