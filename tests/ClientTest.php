<?php

declare(strict_types=1);

namespace Tests\FragSeb\GraphQL;

use Fig\Http\Message\StatusCodeInterface;
use FragSeb\GraphQL\Client;
use FragSeb\GraphQL\ClientInterface;
use FragSeb\GraphQL\Exception\ExceptionInterface;
use FragSeb\GraphQL\Exception\GraphQLException;
use FragSeb\GraphQL\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use function FragSeb\GraphQL\request_builder;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

/**
 * @covers \FragSeb\GraphQL\Client
 * @covers \FragSeb\GraphQL\Exception\GraphQLException
 * @covers \FragSeb\GraphQL\request_builder
 */
final class ClientTest extends TestCase
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var MockObject|\GuzzleHttp\ClientInterface
     */
    private $mockGuzzleClient;

    public function setUp(): void
    {
        $this->mockGuzzleClient = $this->createMock(\GuzzleHttp\ClientInterface::class);
        $this->client = new Client($this->mockGuzzleClient);
    }

    public function testSimpleQueryWhenHasException(): void
    {
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->willThrowException(new TransferException('error'))
        ;

        $this->expectException(ExceptionInterface::class);
        $this->expectException(GraphQLException::class);

        $this->client->query($this->getQuery());
    }

    public function testQuery(): void
    {
        $body = json_encode([
            'data' => [
                'bar' => [
                    'id' => 1,
                    'name' => 'foo',
                    'sub' => [
                        'id' => 2,
                    ],
                ],
            ],
        ]);

        $query = $this->getQuery();

        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with('POST', '', [
                'json' => [
                    'query' => $query,
                    'variables' => [],
                ],
                'headers' => [],
            ])
            ->willReturn(new Response(StatusCodeInterface::STATUS_OK, [], $body))
        ;

        $result = $this->client->query($query);

        self::assertInstanceOf(ResponseInterface::class, $result);
        self::assertSame(json_decode($body, true), $result->getData());
    }

    public function testQueryWithVariables(): void
    {
        $body = json_encode([
            'data' => [
                'bar' => [
                    'id' => 1,
                    'name' => 'foo',
                    'sub' => [
                        'id' => 2,
                    ],
                ],
            ],
        ]);


        $query = <<<'QUERY'
    query Foo($id: String!) {
        bar(id: $id) {
            id
            name
            sub {
                id
            }
        }
    }
QUERY;

        $variables = [
            'id' => 'test',
        ];

        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with('POST', '', [
                'json' => [
                    'query' => $query,
                    'variables' => $variables,
                ],
                'headers' => [],
            ])
            ->willReturn(new Response(StatusCodeInterface::STATUS_OK, [], $body))
        ;

        $result = $this->client->query($query, $variables);

        self::assertInstanceOf(ResponseInterface::class, $result);
        self::assertSame(json_decode($body, true), $result->getData());
    }

    public function testQueryAsync(): void
    {
        $query = <<<'QUERY'
    query Foo($id: String!) {
        bar(id: $id) {
            id
            name
            sub {
                id
            }
        }
    }
QUERY;

        $requestsBuilder = [
            request_builder(['query' => $query, 'variables' => ['id' => 1]], []),
            request_builder(['query' => $query, 'variables' => ['id' => 2]], []),
            request_builder(['query' => $query, 'variables' => ['id' => 3]], []),
            request_builder(['query' => $query, 'variables' => ['id' => 4]], []),
        ];

        self::assertInstanceOf(RequestInterface::class, $requestsBuilder[0]);

        $requests = static function () use ($requestsBuilder) {
            foreach ($requestsBuilder as $request) {
                yield $request;
            }
        };

        $fn = static function (): Promise {
            $promise = new Promise();
            $promise->resolve(new Response());

            return $promise;
        };

        $r4 = new Promise(static function () use (&$r4, $requestsBuilder): void {
            $r4->reject(new RequestException('', $requestsBuilder[3], new Response(StatusCodeInterface::STATUS_BAD_REQUEST)));
        });

//        $r4->reject()

        $handler = new MockHandler([$fn(), $fn(), $fn(), $r4]);

        $client2 = new \GuzzleHttp\Client([
            'handler' => $handler,
            'base_uri' => 'https://www.example.com/graphql',
        ]);

        $client = new Client($client2);

        $onFullfilled = function (ResponseInterface $response): void {
            $this->assertInstanceOf(ResponseInterface::class, $response);
        };

        $onRejecte = function (GraphQLException $exception): void {
            $this->assertInstanceOf(GraphQLException::class, $exception);
        };

        $client->queryAsync($requests, $onFullfilled, $onRejecte);
    }

    private function getQuery(): string
    {
        return <<<'QUERY'
{
    bar(id: $id) {
        id
        name
        sub {
            id
        }
    }
}
QUERY;
    }
}
