<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Validator;

use Nusje2000\ComposerMonolith\Validator\RuleCollection;
use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\Validator;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use PHPStan\Testing\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidate(): void
    {
        $rule = $this->createMock(RuleInterface::class);
        $rule->expects(self::once())->method('execute')->willReturn(
            new ViolationCollection([
                $this->createStub(ViolationInterface::class),
            ])
        );

        $validator = new Validator(new RuleCollection([$rule]));
        $output = $validator->validate(new DependencyGraph('/', new PackageCollection()));

        self::assertCount(1, $output);
    }
}
