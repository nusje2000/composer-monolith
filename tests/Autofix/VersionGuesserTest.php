<?php

declare(strict_types=1);

use Nusje2000\ComposerMonolith\Autofix\VersionGuesser;
use PHPUnit\Framework\TestCase;

final class VersionGuesserTest extends TestCase
{
    /**
     * @var VersionGuesser
     */
    private $guesser;

    /**
     * @dataProvider guessProvider
     *
     * @param array<int|string, string> $constraints
     */
    public function testGuess(array $constraints, ?string $expected): void
    {
        $actual = $this->guesser->guess($constraints);

        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function guessProvider(): array
    {
        return [
            'unresolveable' => [
                ['^1.0', '^2.0'],
                null,
            ],
            'resolveable' => [
                ['^1.3', '^1.0'],
                '^1.3',
            ],
            'invalid' => [
                ['bla'],
                null,
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->guesser = new VersionGuesser();
    }
}
