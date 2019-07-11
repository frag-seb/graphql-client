<?php

declare(strict_types=1);

namespace FragSeb\GraphQL;

use Fig\Http\Message\StatusCodeInterface;
use FragSeb\GraphQL\Transformer\CompoundTransformer;
use FragSeb\GraphQL\Transformer\DataTransformerInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;
use function GuzzleHttp\json_decode;

final class Response implements ResponseInterface
{
    /**
     * @var PsrResponseInterface
     */
    private $response;

    public function __construct(PsrResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion($version): MessageInterface
    {
        $response = $this->response->withProtocolVersion($version);

        return $this->handleResponse($response);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader($name, $value): MessageInterface
    {
        $response = $this->response->withHeader($name, $value);

        return $this->handleResponse($response);
    }

    public function withAddedHeader($name, $value): MessageInterface
    {
        $response = $this->response->withAddedHeader($name, $value);

        return $this->handleResponse($response);
    }

    public function withoutHeader($name): MessageInterface
    {
        $response = $this->response->withoutHeader($name);

        return $this->handleResponse($response);
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $response = $this->response->withBody($body);

        return $this->handleResponse($response);
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = ''): MessageInterface
    {
        $response = $this->response->withStatus($code, $reasonPhrase);

        return $this->handleResponse($response);
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function isSuccessful(): bool
    {
        return $this->response->getStatusCode() >= StatusCodeInterface::STATUS_OK && $this->response->getStatusCode() <= StatusCodeInterface::STATUS_IM_USED;
    }

    public function isClientError(): bool
    {
        return $this->response->getStatusCode() >= StatusCodeInterface::STATUS_BAD_REQUEST && $this->response->getStatusCode() <= StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS;
    }

    public function isServerError(): bool
    {
        return $this->response->getStatusCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR && $this->response->getStatusCode() <= StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED;
    }

    public function getData(?DataTransformerInterface $dataTransformer = null): array
    {
        if (!$this->isSuccessful()) {
            return [];
        }

        $transformers = [];

        if ($dataTransformer !== null) {
            $transformers[] = $dataTransformer;
        }

        return $this->applyTransformers(...$transformers);
    }

    private function applyTransformers(DataTransformerInterface ...$dataTransformers): array
    {
        $data = json_decode((string) $this->response->getBody(), true);

        return (new CompoundTransformer(...$dataTransformers))->transform($data);
    }

    private function handleResponse(PsrResponseInterface $response): self
    {
        if ($response === $this->response) {
            return $this;
        }

        $new = clone $this;
        $new->response = $response;

        return $new;
    }
}
