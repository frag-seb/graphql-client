<?php

declare(strict_types=1);

namespace Tests\FragSeb\GraphQL;

use Fig\Http\Message\StatusCodeInterface;
use FragSeb\GraphQL\Response;
use FragSeb\GraphQL\ResponseInterface;
use FragSeb\GraphQL\Transformer\DataTransformerAwareInterface;
use FragSeb\GraphQL\Transformer\DataTransformerInterface;
use PHPStan\Testing\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @covers \FragSeb\GraphQL\Response
 */
final class ResponseTest extends TestCase
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var MockObject|\Psr\Http\Message\ResponseInterface
     */
    private $mockResponse;


    public function setUp(): void
    {
        $status = StatusCodeInterface::STATUS_OK;
        $headers = [
            'foo' => 'bar',
        ];
        $body = json_encode(['key1' => 'value1']);

        $this->mockResponse = new \GuzzleHttp\Psr7\Response($status, $headers, $body);
        $this->response = new Response($this->mockResponse);
    }

    public function testIsInstance(): void
    {
        self::assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $this->response);
        self::assertInstanceOf(ResponseInterface::class, $this->response);
        self::assertInstanceOf(DataTransformerAwareInterface::class, $this->response);
    }

    public function testGetProtocolVersion(): void
    {
        self::assertSame('1.1', $this->response->getProtocolVersion());
    }

    public function testWithProtocolVersion(): void
    {
        $response = $this->response->withProtocolVersion('1.1');
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame($this->response, $response);

        $response = $this->response->withProtocolVersion('1.2');
        self::assertNotSame($this->response, $response);
    }

    public function testGetHeaders(): void
    {
        self::assertSame(['foo' => ['bar']], $this->response->getHeaders());
    }

    public function testHasHeader(): void
    {
        self::assertTrue($this->response->hasHeader('foo'));
        self::assertFalse($this->response->hasHeader('bar'));
    }

    public function testGetHeader(): void
    {
        self::assertSame(['bar'], $this->response->getHeader('foo'));
    }

    public function testGetHeaderLine(): void
    {
        self::assertSame('bar', $this->response->getHeaderLine('foo'));
    }

    public function testWithHeader(): void
    {
        $response = $this->response->withHeader('baz', 'test');

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertNotSame($this->response, $response);
    }

    public function testWithAddedHeader(): void
    {
        $response = $this->response->withAddedHeader('baz', 'test');

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertNotSame($this->response, $response);
    }

    public function testWithoutHeader(): void
    {
        self::assertTrue($this->response->hasHeader('foo'));

        $response = $this->response->withoutHeader('bar');
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame($this->response, $response);

        $response = $this->response->withoutHeader('foo');

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertNotSame($this->response, $response);

        self::assertFalse($response->hasHeader('foo'));
    }

    public function testGetBody(): void
    {
        $body = $this->response->getBody();

        self::assertInstanceOf(StreamInterface::class, $body);
        $content = $body->getContents();
        self::assertJson($content);

        $content = json_decode($content, true);
        self::assertSame(['key1' => 'value1'], $content);
    }

    public function testWithBody(): void
    {
        $response = $this->response->withBody($this->response->getBody());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame($this->response, $response);

        $response = $this->response->withBody(stream_for(json_encode(['key2' => 'value2'])));

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertNotSame($this->response, $response);
    }

    public function testGetStatusCode(): void
    {
        self::assertSame(StatusCodeInterface::STATUS_OK, $this->response->getStatusCode());
    }

    public function testWithStatusAndReasonPhrase(): void
    {
        self::assertSame(StatusCodeInterface::STATUS_OK, $this->response->getStatusCode());
        self::assertSame('OK', $this->response->getReasonPhrase());

        $response = $this->response->withStatus(StatusCodeInterface::STATUS_ACCEPTED);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertNotSame($this->response, $response);

        self::assertSame(StatusCodeInterface::STATUS_ACCEPTED, $response->getStatusCode());
        self::assertSame('Accepted', $response->getReasonPhrase());
    }

    public function testIsSuccessful(): void
    {
        $response = $this->response->withStatus(StatusCodeInterface::STATUS_OK);
        self::assertTrue($response->isSuccessful());

        $response = $this->response->withStatus(StatusCodeInterface::STATUS_IM_USED);
        self::assertTrue($response->isSuccessful());

        $response = $this->response->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST);
        self::assertFalse($response->isSuccessful());
    }

    public function testIsClientError(): void
    {
        $response = $this->response->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST);
        self::assertTrue($response->isClientError());

        $response = $this->response->withStatus(StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS);
        self::assertTrue($response->isClientError());

        $response = $this->response->withStatus(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        self::assertFalse($response->isClientError());
    }

    public function testIsServerError(): void
    {
        $response = $this->response->withStatus(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        self::assertTrue($response->isServerError());

        $response = $this->response->withStatus(StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED);
        self::assertTrue($response->isServerError());

        $response = $this->response->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST);
        self::assertFalse($response->isServerError());
    }

    public function testGetDataIsFall(): void
    {
        $response = $this->response->withStatus(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

        self::assertSame([], $response->getData());
    }

    public function testGetData(): void
    {
        self::assertSame(['key1' => 'value1'], $this->response->getData());

        $transformer = new class implements DataTransformerInterface
        {
            public function transform(array $data): array
            {
                return $data + ['key2' => 'value2'];
            }
        };

        self::assertSame(['key1' => 'value1', 'key2' => 'value2'], $this->response->getData($transformer));
    }
}
