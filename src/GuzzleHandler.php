<?php declare(strict_types=1);

namespace LukeWaite\RingPhpGuzzleHandler;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future\CompletedFutureArray;
use GuzzleHttp\Ring\Future\FutureArrayInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleHandler
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function __invoke(array $request): FutureArrayInterface
    {
        return new CompletedFutureArray($this->invokeGuzzle($request));
    }

    private function invokeGuzzle(array $request): array
    {
        $url = Core::url($request);
        Core::doSleep($request);

        $stats = null;
        $options = [
            RequestOptions::BODY => Core::body($request),
            RequestOptions::HEADERS => $request['headers'],
            RequestOptions::HTTP_ERRORS => false,
        ];

        if (isset($request['client']['curl'][CURLOPT_USERPWD])) {
            $options['auth'] = explode(':', $request['client']['curl'][CURLOPT_USERPWD]);
        }

        try {
            $start = microtime(true);
            $response = $this->client->request($request['http_method'], $url, $options);
            $end = microtime(true);
        } catch (GuzzleException $exception) {
            return ['error' => $exception] + $this->emptyResponse();
        }

        return $this->processResponse($url, ($end - $start), $response);
    }

    private function processResponse(string $url, float $time, ResponseInterface $response): array
    {
        return [
            'version' => $response->getProtocolVersion(),
            'status' => $response->getStatusCode(),
            'reason' => $response->getReasonPhrase(),
            'headers' => $response->getHeaders(),
            'effective_url' => $url,
            'body' => $this->createStream((string) $response->getBody()),
            'transfer_stats' => $this->createTransferStats($url, $time, $response),
        ];
    }

    private function createTransferStats(string $url, float $time, ResponseInterface $response): array
    {
        return [
            'url' => $url,
            'total_time' => $time,
            'content_type' => $response->getHeaderLine('Content-Type'),
            'http_code' => $response->getStatusCode(),
        ];
    }

    private function createStream($resource)
    {
        if ($resource == '') {
            return null;
        }

        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return null;
        }

        fwrite($stream, $resource);
        fseek($stream, 0);

        return $stream;
    }

    private function emptyResponse()
    {
        return [
            'version' => null,
            'status' => null,
            'reason' => null,
            'headers' => [],
            'effective_url' => null,
            'body' => null,
            'transfer_stats' => [
                'url' => null,
                'total_time' => null,
                'content_type' => null,
                'http_code' => null,
            ],
        ];
    }
}
