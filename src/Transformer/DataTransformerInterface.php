<?php

declare(strict_types=1);

namespace FragSeb\GraphQL\Transformer;

interface DataTransformerInterface
{
    public function transform(array $data): array;
}
