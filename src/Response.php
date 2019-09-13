<?php

namespace MyOperator;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Response {

    public function __construct(GuzzleResponse $streamResponse) {
        $this->response = $streamResponse;
        $this->textResponse = $this->response->getBody()->getContents();
    }

    function __call($method, $args) {
        return \call_user_func_array([$this->response, $method], $args);
    }

    public function getOriginalResponse() {
        return $this->response;
    }

    public function getStatus() {
        return $this->response->getStatusCode();
    }

    public function __toString() {
        return $this->textResponse;
    }

    public function text() {
        return $this->textResponse;
    }

    public function json($arrayable = true) {
        $jsonResponse = json_decode($this->textResponse, $arrayable);
        if($jsonResponse == null && json_last_error_msg() !== null) {
            return $this->textResponse;
        }
        return $jsonResponse;
    }
}
