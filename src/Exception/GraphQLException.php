<?php

declare(strict_types=1);

namespace FragSeb\GraphQL\Exception;

use RuntimeException;
use Throwable;

final class GraphQLException extends RuntimeException implements ExceptionInterface
{
    public function __construct(Throwable $previous, string $message = '', int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }
}
