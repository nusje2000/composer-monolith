<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Composer;

use LogicException;
use Symfony\Component\Finder\SplFileInfo;

final class DefinitionMutator implements DefinitionMutatorInterface
{
    /**
     * @var array<mixed, mixed>
     */
    protected $originalDefinition;

    /**
     * @var array<mixed, mixed>
     */
    protected $mutatedDefinition;

    /**
     * @var SplFileInfo
     */
    protected $fileInfo;

    public function __construct(SplFileInfo $fileInfo)
    {
        $decoded = json_decode($fileInfo->getContents(), true);

        $this->originalDefinition = $decoded;
        $this->mutatedDefinition = $decoded;
        $this->fileInfo = $fileInfo;
    }

    public function setDependency(string $dependency, string $versionConstraint): void
    {
        $this->mutatedDefinition['require'][$dependency] = $versionConstraint;
    }

    public function removeDependency(string $dependency): void
    {
        unset($this->mutatedDefinition['require'][$dependency]);
    }

    public function setDevDependency(string $dependency, string $versionConstraint): void
    {
        $this->mutatedDefinition['require-dev'][$dependency] = $versionConstraint;
    }

    public function removeDevDependency(string $dependency): void
    {
        unset($this->mutatedDefinition['require-dev'][$dependency]);
    }

    public function setReplace(string $name, string $version): void
    {
        $this->mutatedDefinition['replace'][$name] = $version;
    }

    public function removeReplace(string $name): void
    {
        unset($this->mutatedDefinition['replace'][$name]);
    }

    public function isMutated(): bool
    {
        return $this->originalDefinition !== $this->mutatedDefinition;
    }

    public function save(): void
    {
        if (!$this->isMutated()) {
            return;
        }

        $encoded = json_encode($this->mutatedDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded .= PHP_EOL;

        $path = $this->fileInfo->getRealPath();
        if (false === $path) {
            throw new LogicException('Could not write contents.');
        }

        file_put_contents($path, $encoded);
    }
}
