<?php

namespace MyOperator\TransportTests\Unit;

use PHPUnit\Framework\TestCase;
use \MyOperator\Response;
use GuzzleHttp\Psr7\Response as HTTPResponse;

final class ResponseTest extends TestCase
{
    public function setUp() {
        $this->baseurl = 'https://httpbin.org';
    }

    public function test_response_takes_guzzle_response() {
        $httpresponse = new HTTPResponse();
        $response = new Response($httpresponse);
        $this->assertInstanceOf(HTTPResponse::class, $response->getOriginalResponse());
    }

    public function test_response_returns_valid_status() {
        $httpresponse = new HTTPResponse(401, [], "Sample Body");
        $response = new Response($httpresponse);
        $this->assertEquals(401, $response->getStatus());
        $this->assertNotEquals(200, $response->getStatus());
    }

    public function test_response_returning_valid_body() {
        $httpresponse = new HTTPResponse(200, [], "Sample Body");
        $response = new Response($httpresponse);
        $this->assertEquals("Sample Body", (string) $response);
        $this->assertEquals("Sample Body", $response->text());
    }

    public function test_response_returns_validjson() {
        $httpresponse = new HTTPResponse(200, [], json_encode(['a'=> ['b' => 'c']]));
        $response = new Response($httpresponse);
        $this->assertEquals(['a' => ['b' => 'c']], $response->json());
    }

    public function test_response_returns_headers() {
        $httpresponse = new HTTPResponse(200, ['X-api-key' => 'a'], json_encode(['a'=> ['b' => 'c']]));
        $response = new Response($httpresponse);
        $this->assertEquals($httpresponse->getHeaders(), $response->getHeaders());
        $this->assertEquals(['a'], $response->getHeader('X-api-key'));
    }

    public function test_response_return_string_invalidjson() {
        $httpresponse = new HTTPResponse(200, [], 'invalid json');
        $response = new Response($httpresponse);
        $this->assertEquals('invalid json',  $response->json());
    }
}


