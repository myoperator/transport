<?php

namespace MyOperator\TransportTests\Unit;

use PHPUnit\Framework\TestCase;
use MyOperator\Transport\Transport;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

final class TransportTest extends TestCase
{
    public function setUp() {
        $this->baseurl = 'http://0.0.0.0';
    }

    public function getMockHandlerStack($responses = [])
    {
        $stack = HandlerStack::create(new MockHandler(
            $responses
        ));
        return $stack;
    }

    public function getMockClient($responses = [])
    {
        $stack = $this->getMockHandlerStack($responses);
        return new Client(['handler' => $stack]);
    }

    public function getHistoryMockClient($responses = [], &$container = [])
    {
        $stack = $this->getMockHandlerStack(
            $responses
        );
        $history = Middleware::history($container);
        $stack->push($history);
        return new Client(['handler' => $stack]);
    }

    public function test_headers_are_set()
    {
        $transport = new Transport;
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
        $mockClient = $this->getMockClient([
            new Response(200, [], json_encode(['a' => 'b']))
        ]);
        $transport = new Transport;
        $transport->setClient($mockClient);
        $response = $transport->get('/get', ['query' => ['a' => 'b']]);
        $responseArray = json_decode($response, true);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $responseArray);

        $responseArray = $response->json();
        $this->assertEquals(['a' => 'b'], $responseArray);
    }

    public function test_transport_exception_on_nobaseuri()
    {
        $transport = new Transport;
        $this->setExpectedException(\Exception::class);
        $transport->get('/');
    }

    public function test_baseuri_passed_constructor() {
        $transport = new Transport($this->baseurl);
        $history = [];
        $mockClient = $this->getHistoryMockClient([
            new Response(200, [], null)
        ], $history);
        $transport->setClient($mockClient);
        $response = $transport->get('/get', ['query' => ['a' => 'b']]);

        $path = $query = null;
        foreach ($history as $transaction) {
            $path = $transaction['request']->getUri()->getPath();
            $query = $transaction['request']->getUri()->getQuery();
        }
        $this->assertEquals('/get', $path);
        $this->assertEquals('a=b', $query);
    }

    public function test_baseuri_can_be_changed() {
        $transport = new Transport($this->baseurl);
        // $mockClient = $this->getMockClient([
        //     new Response(200, [], json_encode(['a' => 'b']))
        // ]);
        // $transport->setClient($mockClient);
        $client = $transport->getClient();
        $config = $client->getConfig();
        $this->assertEquals($this->baseurl, $config['base_uri']);

        $transport = new Transport('https://www.google.com');
        $client = $transport->getClient();
        $config = $client->getConfig();
        $this->assertEquals('https://www.google.com', $config['base_uri']);
    }

    public function test_timeouts_can_be_set() {
        $transport = new Transport;
        $transport->setTimeout(60); //In seconds
        $config = $transport->getClient()->getConfig();
        $this->assertEquals(60, $config['connect_timeout']);
    }

    public function test_get_response_json_is_array() {
        $transport = new Transport($this->baseurl);
        $mockClient = $this->getMockClient([
            new Response(203, [], json_encode(['a' => 'b']))
        ]);
        $transport->setClient($mockClient);
        $response = $transport->get('/get', ['a' => 'b']);
        $this->assertEquals(203, $response->getStatus());
        $this->assertTrue(is_array($response->json()));
    }

    public function test_post_response_is_returning()
    {
        $transport = new Transport($this->baseurl);
        $mockClient = $this->getMockClient([
            new Response(201, [], json_encode(['json' => ['a' => 'b']]))
        ]);
        $transport->setClient($mockClient);
        $response = $transport->post('/post', ['json' => ['a' => 'b']]);
        $this->assertEquals(201, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response->json()['json']);
    }

    public function test_default_headers_is_json() {
        $transport = new Transport();
        $this->assertEquals('application/json', $transport->getHeader('Content-Type'));
    }

    public function test_post_formdata_response_returning() {
        $transport = new Transport($this->baseurl);
        $mockClient = $this->getMockClient([
            new Response(200, ['Content-Type' => 'application/x-www-form-urlencoded'], json_encode(['form' => ['a' => 'b']]))
        ]);
        $transport->setClient($mockClient);
        $response = $transport->post('/post', ['form_params' => ['a' => 'b']]);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response->json()['form']);
    }

    public function test_setHeader_override_existing_client() {
        $transport = new Transport($this->baseurl);
        $mockClient = $this->getMockClient([
            new Response(200, ['Content-Type' => 'application/x-www-form-urlencoded'], json_encode(['form' => ['a' => 'b']]))
        ]);
        $transport->setClient($mockClient);
        $client1 = $transport->getClient();
        $this->assertTrue(($client1 instanceof Client));
        $this->assertSame($client1, $mockClient);

        $transport->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
        $client2 = $transport->getClient();
        $this->assertTrue(($client2 instanceof \GuzzleHTTP\Client));
        $this->assertNotSame($client1, $client2);
    }

    public function test_guzzle_is_actual_transport()
    {
        $transport = new Transport();
        $client = $transport->getClient();
        $this->assertTrue(($client instanceof \GuzzleHTTP\Client));
    }

    public function test_transport_can_use_guzzle_format()
    {
        $history = [];
        $transport = new Transport($this->baseurl);
        $mockClient = $this->getHistoryMockClient([
            new Response(200, [], json_encode(['a' => 'b']))
        ], $history);

        $transport->setClient($mockClient);
        $response = $transport->post('/post', ['json' => ['a' => 'b']]);

        $path = $body = null;
        foreach ($history as $transaction) {
            $path = $transaction['request']->getUri()->getPath();
            $body = $transaction['request']->getBody();
        }
        $this->assertEquals('/post', $path);
        $this->assertEquals(['a' => 'b'], json_decode($body, true));
    }

    public function test_get_base_url() {
        $transport = new Transport($this->baseurl);
        $this->assertEquals($this->baseurl, $transport->getBaseUrl());
    }
}
