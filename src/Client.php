<?php

declare(strict_types=1);

namespace FragSeb\GraphQL;

use FragSeb\GraphQL\Exception\GraphQLException;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final class Client implements ClientInterface
{
    /**
     * @var GuzzleClientInterface
     */
    private $guzzleClient;

    public function __construct(GuzzleClientInterface $client)
    {
        $this->guzzleClient = $client;
    }

    public function query(string $query, array $variables = [], array $headers = []): ResponseInterface
    {
        try {
            $response = $this->guzzleClient->request('POST', '', [
                'json' => [
                    'query' => $query,
                    'variables' => $variables,
                ],
                'headers' => $headers,
            ]);
        } catch (GuzzleException $exception) {
            throw new GraphQLException($exception, $exception->getMessage(), $exception->getCode());
        }

        return new Response($response);
    }

    public function queryAsync(callable $requests, callable $onSuccessful, callable $onUnsuccessful, int $concurrency = 10): void
    {
        $pool = new Pool($this->guzzleClient, $requests(), [
            'concurrency' => $concurrency,
            'fulfilled' => static function (PsrResponseInterface $response, int $index) use ($onSuccessful): void {
                $onSuccessful(new Response($response), $index);
            },
            'rejected' => static function (RequestException $exception) use ($onUnsuccessful): void {
                $onUnsuccessful(new GraphQLException($exception, $exception->getMessage(), $exception->getCode()));
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
    }
}
