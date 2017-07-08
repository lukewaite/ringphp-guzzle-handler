<?php

namespace LukeWaite\RingPhpGuzzleHandler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future\CompletedFutureArray;
use GuzzleHttp\Ring\Future\FutureArrayInterface;

class GuzzleHandler
{
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * @param $request
     * @return FutureArrayInterface
     */
    public function __invoke($request)
    {
        return new CompletedFutureArray(
            $this->_invokeGuzzle($request)
        );
    }

    public function _invokeGuzzle($request)
    {
        $url = Core::url($request);
        Core::doSleep($request);

        $stats = null;

        try {
            $start = microtime(true);
            $response = $this->client->request(
                $request['http_method'],
                $url,
                [
                    RequestOptions::BODY => Core::body($request),
                    RequestOptions::HEADERS => $request['headers'],
                    RequestOptions::HTTP_ERRORS => false
                ]
            );
            $end = microtime(true);
        } catch (GuzzleException $e) {
            return ['error' => $e];
        }

        return $this->processResponse($url, ($end - $start), $response);
    }

    /**
     * @param $url
     * @param $time
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function processResponse($url, $time, $response)
    {
        return [
            'version' => $response->getProtocolVersion(),
            'status' => $response->getStatusCode(),
            'reason' => $response->getReasonPhrase(),
            'headers' => $response->getHeaders(),
            'effective_url' => $url,
            'body' => $this->createStream((string)$response->getBody()),
            'transfer_stats' => $this->createTransferStats($url, $time, $response)
        ];
    }

    /**
     * @param $url
     * @param $time
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    protected function createTransferStats($url, $time, $response) {
        return [
            'url' => $url,
            'total_time' => $time,
            'content_type' => $response->getHeaderLine('Content-Type'),
            'http_code' => $response->getStatusCode()
        ];
    }

    protected function createStream($resource)
    {
        if ($resource == '') {
            return null;
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $resource);
        fseek($stream, 0);
        return $stream;
    }
}