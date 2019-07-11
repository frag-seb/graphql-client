<?php

declare(strict_types=1);

namespace FragSeb\GraphQL;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use function function_exists;
use function GuzzleHttp\json_encode;

if (!function_exists('request_builder')) {
    function request_builder(array $options = []): RequestInterface
    {
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;
        $version = $options['version'] ?? '1.1';

        if (isset($options['json'])) {
            $body =  json_encode($options['json']);

            unset($options['json']);

            $headers = ['Content-Type' => 'application/json'] + $headers;
        }

        return new Request('POST', '', $headers, $body, $version);
    }
}
