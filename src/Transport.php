<?php

namespace MyOperator;

class Transport {
    public function setBaseUrl($url) {

    }

    public function setHeaders(Array $headers) {
        foreach($headers as $k => $header) {
            $this->headers[$k] = $header;
        }
    }

    public function getClient() {
        return new \GuzzleHttp\Client;
    }

    public function json($path) {
        $response = ['headers' => array_map(function ($val) { return $val; }, $this->headers)];
        return json_encode($response);
    }
}
