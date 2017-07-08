<?php

namespace LukeWaite\RingPhpGuzzleHandler\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Ring\Future\FutureArrayInterface;
use LukeWaite\RingPhpGuzzleHandler\GuzzleHandler;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class GuzzleHandlerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function it_makes_a_request()
    {
        $response = new Response(200, ['Content-Type' => ['application/json']], 'testResponseBody');
        $client = m::mock(Client::class);
        $client->shouldReceive('request')
            ->once()
            ->with('POST', 'https://example.com/', [
                'body' => 'testBody',
                'headers' => ['host'=>['example.com']],
                'http_errors' => false
            ])
            ->andReturn($response);

        $handler = new GuzzleHandler($client);
        $response = $handler([
            'http_method' => 'POST',
            'scheme' => 'https',
            'uri' => '/',
            'headers' => ['host'=>['example.com']],
            'body' => 'testBody'
        ]);
        $this->assertInstanceOf(FutureArrayInterface::class, $response);

        return $response;
    }

    /**
     * @test
     * @depends it_makes_a_request
     */
    public function it_should_have_a_valid_response($response)
    {
        $this->assertEquals('1.1', $response['version']);
        $this->assertEquals('200', $response['status']);
        $this->assertEquals('OK', $response['reason']);
        $this->assertEquals(['Content-Type'=>['application/json']], $response['headers']);
        $this->assertEquals('https://example.com/', $response['effective_url']);
        $this->assertEquals('testResponseBody', stream_get_contents($response['body']));
    }

    /**
     * @test
     * @depends it_makes_a_request
     */
    public function it_should_format_transfer_stats_that_elasticsearch_needs($response)
    {
        $stats = $response['transfer_stats'];
        $this->assertEquals('https://example.com/', $stats['url']);
        $this->assertInternalType('float', $stats['total_time']);
        $this->assertEquals('application/json', $stats['content_type']);
        $this->assertEquals('200', $stats['http_code']);
    }

    /** @test */
    public function it_should_catch_guzzle_exceptions_and_pass_as_an_error()
    {
        $e = new TransferException('Test Exception');
        $client = m::mock(Client::class);
        $client->shouldReceive('request')
            ->once()
            ->andThrow($e);
        $handler = new GuzzleHandler($client);
        $response = $handler([
            'http_method' => 'POST',
            'scheme' => 'https',
            'uri' => '/',
            'headers' => ['host'=>['example.com']],
            'body' => 'testBody'
        ]);

        $this->assertEquals($e, $response['error']);
    }
}