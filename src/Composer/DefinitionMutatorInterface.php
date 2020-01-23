<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Composer;

interface DefinitionMutatorInterface
{
    public function setDependency(string $dependency, string $versionConstraint): void;

    public function removeDependency(string $dependency): void;

    public function setDevDependency(string $dependency, string $versionConstraint): void;

    public function removeDevDependency(string $dependency): void;

    public function setReplace(string $name, string $version): void;

    public function removeReplace(string $name): void;

    public function save(): void;
}
