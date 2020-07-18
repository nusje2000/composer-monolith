<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Composer;

use Nusje2000\ComposerMonolith\Exception\MutatorException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(SplFileInfo $fileInfo, ?LoggerInterface $logger = null)
    {
        $decoded = json_decode($fileInfo->getContents(), true);

        if (!is_array($decoded)) {
            throw new MutatorException(sprintf('Could not decode composer file "%s".', $fileInfo->getRealPath()));
        }

        $this->originalDefinition = $decoded;
        $this->mutatedDefinition = $decoded;
        $this->fileInfo = $fileInfo;
        $this->logger = $logger ?? new NullLogger();
    }

    public function setDependency(string $dependency, string $versionConstraint): void
    {
        $this->logger->notice(
            sprintf('Set dependency on "%s" with version "%s" in "%s".', $dependency, $versionConstraint, $this->fileInfo->getRealPath())
        );

        $this->mutatedDefinition['require'][$dependency] = $versionConstraint;
    }

    public function removeDependency(string $dependency): void
    {
        $this->logger->notice(
            sprintf('Removed dependency on "%s" in "%s".', $dependency, $this->fileInfo->getRealPath())
        );

        unset($this->mutatedDefinition['require'][$dependency]);
    }

    public function setDevDependency(string $dependency, string $versionConstraint): void
    {
        $this->logger->notice(
            sprintf('Set dev dependency on "%s" with version "%s" in "%s".', $dependency, $versionConstraint, $this->fileInfo->getRealPath())
        );

        $this->mutatedDefinition['require-dev'][$dependency] = $versionConstraint;
    }

    public function removeDevDependency(string $dependency): void
    {
        $this->logger->notice(
            sprintf('Removed dev dependency on "%s" in "%s".', $dependency, $this->fileInfo->getRealPath())
        );

        unset($this->mutatedDefinition['require-dev'][$dependency]);
    }

    public function setReplace(string $name, string $version): void
    {
        $this->logger->notice(
            sprintf('Replace for version "%s" of "%s" was added to "%s".', $version, $name, $this->fileInfo->getRealPath())
        );

        $this->mutatedDefinition['replace'][$name] = $version;
    }

    public function removeReplace(string $name): void
    {
        $this->logger->notice(
            sprintf('Replace of "%s" was removed from "%s".', $name, $this->fileInfo->getRealPath())
        );

        unset($this->mutatedDefinition['replace'][$name]);
    }

    public function isMutated(): bool
    {
        return $this->originalDefinition !== $this->mutatedDefinition;
    }

    public function save(): void
    {
        if (!$this->isMutated()) {
            $this->logger->notice(
                sprintf('Skipping save of "%s", no mutations where found.', $this->fileInfo->getRealPath())
            );

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
            // @codeCoverageIgnoreStart
            throw new MutatorException(sprintf('Failed writing to "%s".', $path));
            // @codeCoverageIgnoreEnd
        }

        $this->originalDefinition = $this->mutatedDefinition;

        $this->logger->notice(
            sprintf('New version of "%s" was saved.', $this->fileInfo->getRealPath())
        );
    }

    public function __destruct()
    {
        if ($this->isMutated()) {
            $this->logger->warning('Definition is deconstructed but unsaved changes where found.');
        }
    }
}
