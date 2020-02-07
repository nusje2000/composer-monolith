<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\CodeOwners;

use Aeviiq\Collection\ImmutableObjectCollection;
use ArrayIterator;

/**
 * @phpstan-extends ImmutableObjectCollection<int|string, Rule>
 * @psalm-extends   ImmutableObjectCollection<int|string, Rule>
 *
 * @method ArrayIterator|Rule[] getIterator()
 * @method Rule|null first()
 * @method Rule|null last()
 */
final class RuleCollection extends ImmutableObjectCollection
{
    protected function allowedInstance(): string
    {
        return Rule::class;
    }
}
