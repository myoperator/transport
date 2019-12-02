<?php

namespace MyOperator\TransportTests\Integration;

use PHPUnit\Framework\TestCase;
use \MyOperator\Transport;

final class TransportTest extends TestCase
{
    public function setUp() {
        $this->baseurl = 'http://httpbin.org';
    }

    public function test_headers_are_set()
    {
        $transport = new Transport();
        $transport->setBaseUrl($this->baseurl);
        $transport->setHeaders(['X-Custom-Header' => 'abc', 'Content-Type' => 'application/x-www-form-urlencoded']);
        $headers = $transport->getHeaders();
        $this->assertArrayHasKey('X-Custom-Header', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $header = $transport->getHeader('X-Custom-Header');
        $content_header = $transport->getHeader('Content-Type');
        $this->assertEquals('abc', $header);
        $this->assertEquals('application/x-www-form-urlencoded', $content_header);
    }

    public function test_get_response_is_returning_json()
    {
        $transport = new Transport();
        $transport->setBaseUrl($this->baseurl);
        //Test Query string param style
        $response = $transport->get('/get', ['query' => ['a' => 'b']]);
        $responseArray = json_decode($response, true);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $responseArray['args']);

        $responseArray = $response->json();
        $this->assertEquals(['a' => 'b'], $responseArray['args']);
    }

    public function test_transport_exception_on_nobaseuri()
    {
        $transport = new Transport();
        $this->setExpectedException(\Exception::class);
        $transport->get('/');
    }

    public function test_baseuri_passed_constructor() {
        $transport = new Transport($this->baseurl);
        $response = $transport->get('/get', ['query' => ['a' => 'b']]);
        $this->assertEquals(['a' => 'b'], $response->json()['args']);
    }

    public function test_baseuri_can_be_changed() {
        $transport = new Transport($this->baseurl);
        $client = $transport->getClient();
        $config = $client->getConfig();
        $this->assertEquals($this->baseurl, $config['base_uri']);

        $transport = new Transport('https://www.google.com');
        $client = $transport->getClient();
        $config = $client->getConfig();
        $this->assertEquals('https://www.google.com', $config['base_uri']);
    }

    public function test_timeouts_can_be_set() {
        $transport = new Transport();
        $transport->setTimeout(60); //In seconds
        $config = $transport->getClient()->getConfig();
        $this->assertEquals(60, $config['connect_timeout']);
    }

    public function test_get_response_json_is_array() {
        $transport = new Transport($this->baseurl);
        $response = $transport->get('/get', ['a' => 'b']);
        $this->assertEquals(200, $response->getStatus());
        $this->assertTrue(is_array($response->json()));
    }

    public function test_post_response_is_returning()
    {
        $transport = new Transport($this->baseurl);
        $response = $transport->post('/post', ['json' => ['a' => 'b']]);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response->json()['json']);
    }

    public function test_default_headers_is_json() {
        $transport = new Transport();
        $this->assertEquals('application/json', $transport->getHeader('Content-Type'));
    }

    public function test_post_formdata_response_returning() {
        $transport = new Transport($this->baseurl);
        $transport->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
        $response = $transport->post('/post', ['form_params' => ['a' => 'b']]);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response->json()['form']);

        // Test with our data
        // Can be body, form_params, or multiparts
        $response = $transport->post('/post', ['form_params' => ['a' => 'b']]);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response->json()['form']);
    }

    public function test_post_without_data() {
        $transport = new Transport($this->baseurl);
        $transport->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
        $response = $transport->post('/post');
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals([], $response->json()['form']);
    }

    public function test_guzzle_is_actual_transport()
    {
        $transport = new Transport();
        $client = $transport->getClient();
        $this->assertTrue(($client instanceof \GuzzleHTTP\Client));
    }

    public function test_transport_can_use_guzzle_format()
    {
        $transport = new Transport($this->baseurl);
        //$transport->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
        $response = $transport->post('/post', ['json' => ['a' => 'b']]);
        $this->assertEquals(['a' => 'b'], $response->json()['json']);
    }

    public function test_get_base_url() {
        $transport = new Transport($this->baseurl);
        $this->assertEquals($this->baseurl, $transport->getBaseUrl());
    }

    public function test_transport_uses_params()
    {
        $transport = new Transport($this->baseurl);
        //$transport->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
        $response = $transport->post('/post', ['json' => ['a' => 'b']]);
        $this->assertEquals(['a' => 'b'], $response->json()['json']);
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));

        $transport->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
        $response = $transport->post('/post', ['a' => 'b']);
        $this->assertEquals(['a' => 'b'], $response->json()['form']);
        $this->assertEquals(
            'application/x-www-form-urlencoded',
            $response->json()['headers']['Content-Type']
        );

        $transport->setHeaders(['Content-Type' => 'multipart/form-data']);
        $response = $transport->post('/post', [
            ['name' => 'a', 'contents' => 'b']
        ]);
        $this->assertEquals(['a' => 'b'], $response->json()['form']);
        $this->assertContains(
            'multipart/form-data',
            $response->json()['headers']['Content-Type']
        );
    }
}
