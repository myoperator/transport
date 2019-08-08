<?php

namespace MyOperator;

class Transport {

    private $defaultOpts = [
        'debug' => false,
        'connect_timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ];

    public function __construct($baseuri = null, $headers=[]) {
        $this->baseurl = $baseuri;
        $this->headers = $headers;
        $client = $this->getClient();
        $this->client = $client;
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

    public function setHeaders(Array $headers) {
        foreach($headers as $k => $header) {
            $this->headers[$k] = $header;
        }

        $this->updateClient();
        return $this;
    }

    public function setTimeout($timeout = 30) {
        $this->defaultOpts['connect_timeout'] = $timeout;
        return $this;
    }

    private function updateClient($opts = []) {
        $this->client = $this->getClient($opts);
        return $this;
    }

    public function getHeaders() {
        return array_merge($this->headers, $this->defaultOpts['headers']);
    }

    public function getHeader($header) {
        $headers = $this->getHeaders();
        return array_key_exists($header, $headers) ? $headers[$header]: [];
    }

    public function getClient($opts = []) {
        $opts = array_merge($opts, $this->defaultOpts);
        return new \GuzzleHttp\Client([
            'base_uri' => $this->baseurl,
            'headers' => $this->getHeaders(),
            'connect_timeout' => $opts['connect_timeout'],
            'debug' => $opts['debug']
        ]);
    }

    private function getFormParam()
    {
        $headers = $this->client->getConfig('headers');
        $content_type = array_filter($headers, function ($v, $k){
            return strtolower($k) == 'content-type';
        }, ARRAY_FILTER_USE_BOTH);
        $param = 'json';
        switch($content_type) {
            case 'application/x-www-form-urlencoded': $param = 'form_params'; break;
            case 'application/json': $param = 'json'; break;
            case 'multipart/form-data': $param = 'multipart'; break;
        }
        return $param;
    }

    public function get($path, $query_params=[]) {
//        $response = ['headers' => array_map(function ($val) { return $val; }, $this->getHeaders())];
        $response = !empty($query_params) ? $this->client->request('GET', $path, ['query' => $query_params]) : $this->client->get($path);
        return new Response($response);
    }

    public function post($path, $data, $headers = []) {
        $param = $this->getFormParam();
        $response = $this->client->request('POST', $path, [$param => $data]);
        return new Response($response);
    }
}
