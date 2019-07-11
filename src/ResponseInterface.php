<?php

declare(strict_types=1);

namespace FragSeb\GraphQL;

use FragSeb\GraphQL\Transformer\DataTransformerAwareInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface extends DataTransformerAwareInterface, PsrResponseInterface
{
    public function isSuccessful(): bool;

    public function isClientError(): bool;

    public function isServerError(): bool;
}
