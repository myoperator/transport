<?php

namespace MyOperator\Transport;

use GuzzleHttp\RequestOptions;

class Transport {

    protected $defaultOpts = [
        'debug' => false,
        'connect_timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'allow_redirects' => true
    ];

    public function __construct($baseuri = null, $headers=[]) {
        $this->baseurl = $baseuri;
        $this->headers = $headers;
        $this->client = $this->getClient();
        $this->requestOptions = (new \ReflectionClass(RequestOptions::class))->getConstants();
    }

    public function setBaseUrl($url) {
        $this->baseurl = $url;
        $this->updateClient();
        return $this;
    }

    public function getBaseUrl() {
        return $this->baseurl;
    }

    public function setDebug($debug = false) {
        $this->defaultOpts['debug'] = $debug;
        return $this;
    }

    public function setHeaders($headers, $value = null) {
        if(($value !== null) && !is_array($headers)) {
            $headers = [$headers => $value];
        }
        foreach($headers as $k => $header) {
            $this->headers[$k] = $header;
            foreach($this->defaultOpts['headers'] as $key => $defaultHeaders) {
                if($key == $k) {
                    $this->defaultOpts['headers'][$key] = $header;
                }
            }
        }

        $this->updateClient();
        return $this;
    }

    public function setTimeout($timeout = 30) {
        $this->defaultOpts['connect_timeout'] = $timeout;
        $this->updateClient();
        return $this;
    }

    private function updateClient($opts = []) {
        $this->client = $this->createClient($opts);
        return $this;
    }

    public function setClient(\GuzzleHttp\Client $client) {
        $this->client = $client;
        return $this;
    }

    public function getHeaders() {
        return array_merge($this->headers, $this->defaultOpts['headers']);
    }

    public function getHeader($header) {
        $headers = $this->getHeaders();
        return array_key_exists($header, $headers) ? $headers[$header]: [];
    }

    public function createClient($opts = []) {
        $opts = array_merge($this->defaultOpts, $opts);
        return new \GuzzleHttp\Client([
            'base_uri' => $this->baseurl,
            'headers' => $this->getHeaders(),
            'connect_timeout' => $opts['connect_timeout'],
            'debug' => $opts['debug'],
            'allow_redirects' => $opts['allow_redirects']
        ]);
    }

    public function getClient() {
        if(!isset($this->client))  {
            $this->client = $this->createClient();
        }
        return $this->client;
    }

    private function getFormParam()
    {
        $headers = $this->client->getConfig('headers');
	    if(version_compare(phpversion(), '5.6.0', '<')){
            $content_type = array_map(function($k, $v){
                return [strtolower($k) => $v];
            }, array_keys($headers), $headers);
            $content_type = array_intersect_key($content_type, array('content-type'));
        } else {
            $content_type = array_filter($headers, function ($v, $k){
                return strtolower($k) == 'content-type';
            }, ARRAY_FILTER_USE_BOTH);
            $content_type = array_map(function($k, $v){
		        return [strtolower($k) => $v];
	        }, array_keys($content_type), $content_type);
        }
	    $content_type = (isset($content_type[0]) && is_array($content_type[0])) ? $content_type[0] : $content_type; 
        $content_type = isset($content_type['content-type']) ? $content_type['content-type'] : null;
        $param = 'json';
        switch($content_type) {
            case 'application/x-www-form-urlencoded': $param = 'form_params'; break;
            case 'application/json': $param = 'json'; break;
            case 'multipart/form-data': $param = 'multipart'; break;
        }
        return $param;
    }

    function __call($method, $args) {
        $response = \call_user_func_array([$this->getClient(), $method], $args);
        return new Response($response);
    }

    function get($path, $data=[], $options=[]) {
        if(!(array_intersect(array_keys($data), array_values($this->requestOptions)))) {
            $data = ['query' => $data];
        }
        $options = array_merge($data, $options);
        return self::__call(__FUNCTION__, [$path, $options]);
    }

    function post($path, $data=[], $options=[]) {
       if(!(array_intersect(array_keys($data), array_values($this->requestOptions)))) {
           $param = $this->getFormParam();
           $data = [$param => $data];
       }
       $options = array_merge($data, $options);
       return self::__call(__FUNCTION__, [$path, $options]);
    }
}
