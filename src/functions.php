<?php

declare(strict_types=1);

namespace FragSeb\GraphQL;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use function function_exists;
use function GuzzleHttp\json_encode;

if (!function_exists('request_builder')) {
    function request_builder(string $query, array $variables = [], array $headers = []): RequestInterface
    {
        $body = [
            'query' => $query,
            'variables' => $variables,
        ];

        return new Request('POST', '', ['Content-Type' => 'application/json'] + $headers, json_encode($body));
    }
}
