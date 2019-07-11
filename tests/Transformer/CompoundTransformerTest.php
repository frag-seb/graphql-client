<?php

declare(strict_types=1);

namespace Tests\FragSeb\GraphQL\Transformer;

use FragSeb\GraphQL\Transformer\CompoundTransformer;
use FragSeb\GraphQL\Transformer\DataTransformerInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @covers \FragSeb\GraphQL\Transformer\CompoundTransformer
 */
final class CompoundTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $data1 = ['key1' => 'value1'];
        $data2 = ['key2' => 'value2'];
        $data3 = ['key3' => 'value3'];

        $transformer = new CompoundTransformer(
            $this->createTransformer($data2, $data1),
            $this->createTransformer($data3, $data1 + $data2)
        );

        $expectedData = $data1 + $data2 + $data3;
        self::assertSame($expectedData, $transformer->transform($data1));
    }

    private function createTransformer(array $data, array $assertData): DataTransformerInterface
    {
        return new class ($data, $assertData) implements DataTransformerInterface
        {
            /**
             * @var array
             */
            private $data;

            /**
             * @var array
             */
            private $assertData;

            public function __construct(array $data, array $assertData)
            {
                $this->data = $data;
                $this->assertData = $assertData;
            }

            public function transform(array $data): array
            {
                Assert::assertSame($this->assertData, $data);

                return $data + $this->data;
            }
        };
    }
}
