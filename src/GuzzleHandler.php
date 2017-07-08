<?php

namespace LukeWaite\RingPhpGuzzleHandler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future\CompletedFutureArray;
use GuzzleHttp\Ring\Future\FutureArrayInterface;
use GuzzleHttp\Stream\Stream;

class GuzzleHandler
{
    private $client;

    public function __construct($config = [])
    {
        $this->client = new Client($config);
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
            $start = microtime();
            $response = $this->client->request(
                $request['http_method'],
                $url,
                [
                    RequestOptions::BODY => Core::body($request),
                    RequestOptions::HEADERS => $request['headers'],
                    RequestOptions::HTTP_ERRORS => false
                ]
            );
            $end = microtime();
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
            'body' => $response->getBody(),
            'transfer_stats' => [
                'total_time' => $time
            ]
        ];
    }
}