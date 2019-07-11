<?php

declare(strict_types=1);

namespace FragSeb\GraphQL;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use function function_exists;
use function GuzzleHttp\json_encode;

if (!function_exists('request_builder')) {
    function request_builder(array $payload, array $headers = []): RequestInterface
    {
        $headers = ['Content-Type' => 'application/json'] + $headers;

        return new Request('POST', '', $headers, json_encode($payload));
    }
}
