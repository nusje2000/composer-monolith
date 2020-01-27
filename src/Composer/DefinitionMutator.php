<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Composer;

use Nusje2000\ComposerMonolith\Exception\MutatorException;
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

        if (!is_array($decoded)) {
            throw new MutatorException(sprintf('Could not decode composer file "%s".', $fileInfo->getRealPath()));
        }

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

        $path = $this->fileInfo->getRealPath();
        if (false === $path) {
            throw new MutatorException('Could not write contents because the path could not be resolved.');
        }

        if (!$this->fileInfo->isWritable()) {
            throw new MutatorException(sprintf('"%s" is not writeable.', $path));
        }

        $encoded = json_encode($this->mutatedDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded .= PHP_EOL;

        $success = file_put_contents($path, $encoded);
        if (false === $success) {
            throw new MutatorException(sprintf('Failed writing to "%s".', $path));
        }
    }
}
