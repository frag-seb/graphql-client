<?php

declare(strict_types=1);

namespace FragSeb\GraphQL\Transformer;

use function array_walk;

final class CompoundTransformer implements DataTransformerInterface
{
    /**
     * @var DataTransformerInterface[]
     */
    private $transformers;

    public function __construct(DataTransformerInterface ...$transformers)
    {
        $this->transformers = $transformers;
    }

    public function transform(array $data): array
    {
        array_walk($this->transformers, static function (DataTransformerInterface $transformer) use (&$data): void {
            $data = $transformer->transform($data);
        });

        return $data;
    }
}
