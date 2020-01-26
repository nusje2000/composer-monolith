<?php

declare(strict_types=1);

use Nusje2000\ComposerMonolith\Autofix\FixerCollection;
use Nusje2000\ComposerMonolith\Autofix\FixerInterface;
use PHPUnit\Framework\TestCase;

final class FixerCollectionTest extends TestCase
{
    public function testConstruct(): void
    {
        $fixers = [
            $this->createStub(FixerInterface::class),
        ];

        $collection = new FixerCollection($fixers);

        self::assertSame($fixers, $collection->toArray());
    }
}
