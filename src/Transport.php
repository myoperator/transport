<?php

namespace MyOperator;

class Transport {

    private $defaultOpts = [
        'debug' => false,
        'connect_timeout' => 2,
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ];

    public function __construct(HTTPClient $client = null) {
        $this->headers = [];
        if(!$client)
            $client = $this->getClient();
        $this->client = $client;
    }

    public function setBaseUrl($url) {
        $this->baseurl = $url;
        $this->updateClient();
        return $this;
    }

    public function setHeaders(Array $headers) {
        foreach($headers as $k => $header) {
            $this->headers[$k] = $header;
        }

        $this->updateClient();
        return $this;
    }

    private function updateClient($opts = []) {
        $this->client = $this->getClient($opts);
        return $this;
    }

    public function getHeaders() {
        return array_merge($this->headers, $this->defaultOpts['headers']);
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

    public function get($path, $query_params=[]) {
//        $response = ['headers' => array_map(function ($val) { return $val; }, $this->getHeaders())];
        $response = !empty($query_params) ? $this->client->request('GET', $path, ['query' => $query_params]) : $this->client->get($path);
        return $response->getBody()->getContents();
    }
}
