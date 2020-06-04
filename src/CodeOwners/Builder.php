<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\CodeOwners;

use Nusje2000\DependencyGraph\DependencyGraph;

final class Builder
{
    public static function fromGraph(DependencyGraph $graph): File
    {
        $rules = [];

        $rootPath = $graph->getRootPath();

        foreach ($graph->getPackages() as $package) {
            if ($package->isFromVendor()) {
                continue;
            }

            foreach ($package->getAuthors() as $author) {
                if (null === $author->getEmail()) {
                    continue;
                }

                $rules[] = new Rule(
                    $author->getEmail(),
                    str_replace([$rootPath, DIRECTORY_SEPARATOR], ['', '/'], $package->getPackageLocation()) . '/'
                );
            }
        }

        return new File(new RuleCollection($rules));
    }
}
