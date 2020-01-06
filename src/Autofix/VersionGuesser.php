<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix;

use Composer\Semver\VersionParser;
use Throwable;

final class VersionGuesser
{
    /**
     * @var VersionParser
     */
    private $versionParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser();
    }

    /**
     * @param array<int|string, string> $versionConstraints
     *
     * @return string|null
     */
    public function guess(array $versionConstraints): ?string
    {
        try {
            foreach ($versionConstraints as $baseVersionString) {
                $baseVersionConstraint = $this->versionParser->parseConstraints($baseVersionString);

                $incompatibleVersions = array_filter(
                    $versionConstraints,
                    function (string $compareVersionString) use ($baseVersionConstraint): bool {
                        $compareVersionConstraint = $this->versionParser->parseConstraints($compareVersionString);

                        return !$baseVersionConstraint->matches($compareVersionConstraint);
                    }
                );

                if (empty($incompatibleVersions)) {
                    return $baseVersionString;
                }
            }
        } catch (Throwable $throwable) {
            return null;
        }

        return null;
    }
}
