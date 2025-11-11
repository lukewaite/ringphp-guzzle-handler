<?php declare(strict_types=1);

namespace LukeWaite\RingPhpGuzzleHandler\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Ring\Future\FutureArrayInterface;
use LukeWaite\RingPhpGuzzleHandler\GuzzleHandler;
use PHPUnit\Framework\TestCase;

class GuzzleHandlerTest extends TestCase
{
    /** @test */
    public function it_makes_a_request()
    {
        $response = new Response(200, ['Content-Type' => ['application/json']], 'testResponseBody');

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('requestAsync')
            ->with('POST', 'https://example.com/', [
                'body' => 'testBody',
                'headers' => ['host'=>['example.com']],
                'http_errors' => false
            ])
            ->willReturn(new FulfilledPromise($response));

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

    /** @test */
    public function it_makes_requests_with_authentication()
    {
        $response = new Response(200, ['Content-Type' => ['application/json']], 'testResponseBody');

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('requestAsync')
            ->with('POST', 'https://example.com/', [
                'body' => 'testBody',
                'headers' => ['host' => ['example.com']],
                'http_errors' => false,
                'auth' => ['user', 'password']
            ])
            ->willReturn(new FulfilledPromise($response));

        $handler = new GuzzleHandler($client);
        $response = $handler([
            'http_method' => 'POST',
            'scheme' => 'https',
            'uri' => '/',
            'headers' => ['host'=>['example.com']],
            'body' => 'testBody',
            'client' => ['curl' => [CURLOPT_USERPWD => 'user:password']]
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
        $this->assertIsFloat($stats['total_time']);
        $this->assertEquals('application/json', $stats['content_type']);
        $this->assertEquals('200', $stats['http_code']);
    }

    /** @test */
    public function it_should_catch_guzzle_exceptions_and_pass_as_an_error()
    {
        $exception = new TransferException('Test Exception');

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('requestAsync')
            ->with('POST', 'https://example.com/', [
                'body' => 'testBody',
                'headers' => ['host' => ['example.com']],
                'http_errors' => false,
            ])
            ->willReturn(new RejectedPromise($exception));

        $handler = new GuzzleHandler($client);
        $response = $handler([
            'http_method' => 'POST',
            'scheme' => 'https',
            'uri' => '/',
            'headers' => ['host'=>['example.com']],
            'body' => 'testBody'
        ]);

        $this->assertEquals($exception, $response['error']);
        $this->assertSame($response['effective_url'], '');
    }
}
