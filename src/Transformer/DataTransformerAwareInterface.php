<?php

declare(strict_types=1);

namespace FragSeb\GraphQL\Transformer;

interface DataTransformerAwareInterface
{
    public function getData(?DataTransformerInterface $dataTransformer = null): array;
}
