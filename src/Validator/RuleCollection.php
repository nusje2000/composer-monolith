<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator;

use Aeviiq\Collection\ObjectCollection;
use ArrayIterator;

/**
 * @phpstan-extends ObjectCollection<int|string, RuleInterface>
 * @psalm-extends   ObjectCollection<int|string, RuleInterface>
 *
 * @method ArrayIterator|RuleInterface[] getIterator()
 * @method RuleInterface|null first()
 * @method RuleInterface|null last()
 */
final class RuleCollection extends ObjectCollection
{
    protected function allowedInstance(): string
    {
        return RuleInterface::class;
    }
}
