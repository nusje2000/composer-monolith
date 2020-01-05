<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix;

use Aeviiq\Collection\ObjectCollection;
use ArrayIterator;

/**
 * @phpstan-extends ObjectCollection<int|string, FixerInterface>
 * @psalm-extends   ObjectCollection<int|string, FixerInterface>
 *
 * @method ArrayIterator|FixerInterface[] getIterator()
 * @method FixerInterface|null first()
 * @method FixerInterface|null last()
 */
final class FixerCollection extends ObjectCollection
{
    protected function allowedInstance(): string
    {
        return FixerInterface::class;
    }
}
