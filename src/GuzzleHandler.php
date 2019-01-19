<?php declare(strict_types=1);

namespace LukeWaite\RingPhpGuzzleHandler;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future\FutureArray;
use GuzzleHttp\Ring\Future\FutureArrayInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;

class GuzzleHandler
{
    private $client;

    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function __invoke(array $request): FutureArrayInterface
    {
        $defered = new Deferred();
        $promise = $this->invokeGuzzle($request)->then([$defered, 'resolve'], [$defered, 'resolve']);

        return new FutureArray(
            $defered->promise(),
            [$promise, 'wait'],
            [$promise, 'cancel']
        );
    }

    private function invokeGuzzle(array $request): PromiseInterface
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

        $start = \microtime(true);
        return $this->client->requestAsync($request['http_method'], $url, $options)
            ->then(
                function ($response) use ($url, $start) {
                    return $this->processResponse($url, (\microtime(true) - $start), $response);
                },
                function (GuzzleException $exception) {
                    return ['error' => $exception] + $this->emptyResponse();
                }
            );
    }

    private function processResponse(string $url, float $time, ResponseInterface $response): array
    {
        return [
            'version' => $response->getProtocolVersion(),
            'status' => $response->getStatusCode(),
            'reason' => $response->getReasonPhrase(),
            'headers' => $response->getHeaders(),
            'effective_url' => $url,
            'body' => StreamWrapper::getResource($response->getBody()),
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

    private function emptyResponse(): array
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
