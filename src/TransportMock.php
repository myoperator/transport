<?php

namespace MyOperator;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class TransportMock extends Transport {

    private $queues = [];

    public function createResponse($body = [], $headers=[], $status_code=200) {
        return new GuzzleResponse($status_code, $headers, $body);
    }

    public function mock() {
        $mockHandler = new MockHandler($this->queues);
        $handler = HandlerStack::create($mockHandler);
        $this->client = new Client(['handler' => $handler]);
    }

    public function queue(GuzzleResponse $response) {
        $this->queues[] = $response;
    }

    public function clearQueue() {
        $this->queues = [];
        $this->mock();
    }

    public function get($path, $query_params = []) {
        $response = !empty($query_params) ? $this->client->request('GET', $path, ['query' => $query_params]) : $this->client->get($path);
        return new Response($response);
    }
}
