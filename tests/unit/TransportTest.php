<?php

use PHPUnit\Framework\TestCase;
use \MyOperator\Transport;

final class TransportTest extends TestCase
{
    public function setUp() {
        $this->baseurl = 'https://httpbin.org';
    }

    public function test_headers_are_set()
    {
        $transport = new Transport();
        $transport->setBaseUrl($this->baseurl);
        $transport->setHeaders(['X-Custom-Header' => 'abc']);
        $response = $transport->json('/get');
        $this->assertTrue(is_string($response));
        $response = json_decode($response, true);
        $this->assertEquals('abc', $response['headers']['X-Custom-Header']);
    }

    public function test_guzzle_is_actual_transport()
    {
        $transport = new Transport();
        $client = $transport->getClient();
        $this->assertTrue(($client instanceof \GuzzleHTTP\Client));
    }
}
