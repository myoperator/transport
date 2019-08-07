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
        $response = $transport->get('/get');
        $this->assertTrue(is_string($response));
        $response = json_decode($response, true);
        $this->assertEquals('abc', $response['headers']['X-Custom-Header']);
    }

    public function test_get_response_is_returning()
    {
        $transport = new Transport();
        $transport->setBaseUrl($this->baseurl);
        //Test Query string param style
        $response = $transport->get('/get', ['a' => 'b']);
        $response = json_decode($response, true);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response['args']);
    }
/*
    public function test_post_response_is_returning()
    {
        $transport = new Transport();
        $transport->setBaseUrl($this->baseurl);
        //Test Query string param style
        $response = $transport->get('/get', ['a' => 'b']);
        $response = json_decode($response, true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['a' => 'b'], $response['args']);

    }
 */

    public function test_guzzle_is_actual_transport()
    {
        $transport = new Transport();
        $client = $transport->getClient();
        $this->assertTrue(($client instanceof \GuzzleHTTP\Client));
    }
}
