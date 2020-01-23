<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Composer;

use Nusje2000\ComposerMonolith\Exception\ComposerException;
use Symfony\Component\Finder\SplFileInfo;

final class DefinitionMutator implements DefinitionMutatorInterface
{
    /**
     * @var array<mixed, mixed>
     */
    protected $definition;

    /**
     * @var SplFileInfo
     */
    protected $fileInfo;

    /**
     * @param array<mixed, mixed> $definition
     */
    public function __construct(SplFileInfo $fileInfo)
    {
        $this->definition = json_decode($fileInfo->getContents(), true);
        $this->fileInfo = $fileInfo;
    }

    public function setDependency(string $dependency, string $versionConstraint): void
    {
        $this->definition['require'][$dependency] = $versionConstraint;
    }

    public function removeDependency(string $dependency): void
    {
        unset($this->definition['require'][$dependency]);
    }

    public function setDevDependency(string $dependency, string $versionConstraint): void
    {
        $this->definition['require-dev'][$dependency] = $versionConstraint;
    }

    public function removeDevDependency(string $dependency): void
    {
        unset($this->definition['require-dev'][$dependency]);
    }

    public function setReplace(string $name, string $version): void
    {
        $this->definition['replace'][$name] = $version;
    }

    public function removeReplace(string $name): void
    {
        unset($this->definition['replace'][$name]);
    }

    public function save(): void
    {
        $encoded = json_encode($this->definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!is_string($encoded)) {
            throw new ComposerException(sprintf('Could not encode definition due to "%s".', json_last_error_msg()));
        }

        file_put_contents($this->fileInfo->getRealPath(), $encoded);
    }
}
