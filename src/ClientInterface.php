<?php

declare(strict_types=1);

namespace FragSeb\GraphQL;

interface ClientInterface
{
    public function query(string $query, array $variables = [], array $headers = []): ResponseInterface;

    public function queryAsync(callable $requests, callable $onSuccessful, callable $onUnsuccessful, int $concurrency = 10): void;
}
