<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Validator\OutputFormatter;

use Nusje2000\ComposerMonolith\Formatter\OutputFormatter;
use PHPStan\Testing\TestCase;

final class OutputFormatterTest extends TestCase
{
    public function testOutputFormatter(): void
    {
        $outputFormatter = new OutputFormatter();

        self::assertTrue($outputFormatter->isDecorated());
        self::assertTrue($outputFormatter->hasStyle('error'));
        self::assertTrue($outputFormatter->hasStyle('success'));
        self::assertTrue($outputFormatter->hasStyle('dependency'));
        self::assertTrue($outputFormatter->hasStyle('package'));
        self::assertTrue($outputFormatter->hasStyle('version'));
        self::assertTrue($outputFormatter->hasStyle('violation'));
    }
}
