<?php

use PHPUnit\Framework\TestCase;
use \MyOperator\TransportMock;

final class TransportMockTest extends TestCase
{

    public function test_get_response_is_mocking_json()
    {
        $transport = new TransportMock();
        $mockResponse = $transport->createResponse(json_encode(['a' => 'b']), []);
        $transport->queue($mockResponse);
        $transport->mock();

        $response = $transport->get('/get', ['a' => 'b']);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response->json());
    }

    public function test_unauthorized_response_throw_exception() {
        $transport = new TransportMock();
        $mockResponse = $transport->createResponse(json_encode(['a' => 'b']), [], 401);
        $transport->queue($mockResponse);
        $transport->mock();

        try{
            $response = $transport->get('/get', ['a' => 'b']);
        } catch (\Exception $e) {
            $this->assertEquals(401, $e->getCode());
            return;
        }

        $this->fail('Unauthorized requests did not throw any exception');
    }

    public function tests_500errors_throws_exceptions() {
        $transport = new TransportMock();
        $mockResponse = $transport->createResponse(json_encode(['a' => 'b']), [], 500);
        $transport->queue($mockResponse);
        $transport->mock();

        try{
            $response = $transport->get('/get', ['a' => 'b']);
        } catch (\Exception $e) {
            $this->assertEquals(500, $e->getCode());
            return;
        }

        $this->fail('Server errors did not throw any exception');
    }

    public function test_setting_header_still_mocks() {
        $transport = new TransportMock();
        $mockResponse = $transport->createResponse(json_encode(['a' => 'b']), []);
        $transport->queue($mockResponse);
        $transport->mock();


        $transport->setHeaders(['X' => 'y']);
        $response = $transport->get('/get', ['a' => 'b']);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response->json());
    }

    public function tests_clear_queue_throw_exception() {
        $transport = new TransportMock();
        $mockResponse = $transport->createResponse(json_encode(['a' => 'b']), []);
        $transport->queue($mockResponse);
        $transport->mock();

        $response = $transport->get('/get', ['a' => 'b']);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['a' => 'b'], $response->json());

        $transport->clearQueue();

        try{
            $response = $transport->get('/get', ['a' => 'b']);
        } catch (\OutOfBoundsException $e) {
            $this->assertEquals('Mock queue is empty', $e->getMessage());
            return;
        }

        $this->fail('Empty queue did not throw exception');
    }
}

