<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Mutator;

use Nusje2000\ComposerMonolith\Composer\DefinitionMutator;
use Nusje2000\ComposerMonolith\Exception\MutatorException;
use PHPStan\Testing\TestCase;
use Symfony\Component\Finder\SplFileInfo;

final class DefinitionMutatorTest extends TestCase
{
    public function testConstructWithInvalidJson(): void
    {
        $fileInfo = $this->createStub(SplFileInfo::class);
        $fileInfo->method('getContents')->willReturn('');

        $this->expectException(MutatorException::class);
        new DefinitionMutator($fileInfo);
    }

    public function testSetDependency(): DefinitionMutator
    {
        $mutator = $this->createMutator();

        $mutator->setDependency('some/package-2', '^2.0');
        $this->addToAssertionCount(1);

        return $mutator;
    }

    /**
     * @depends testSetDependency
     */
    public function testRemoveDependency(DefinitionMutator $mutator): DefinitionMutator
    {
        $mutator->removeDependency('some/package');
        $this->addToAssertionCount(1);

        return $mutator;
    }

    /**
     * @depends testRemoveDependency
     */
    public function testSetDevDependency(DefinitionMutator $mutator): DefinitionMutator
    {
        $mutator->setDevDependency('some/dev-package-2', '^2.0');
        $this->addToAssertionCount(1);

        return $mutator;
    }

    /**
     * @depends testSetDevDependency
     */
    public function testRemoveDevDependency(DefinitionMutator $mutator): DefinitionMutator
    {
        $mutator->removeDevDependency('some/dev-package');
        $this->addToAssertionCount(1);

        return $mutator;
    }

    /**
     * @depends testRemoveDevDependency
     */
    public function testSetReplace(DefinitionMutator $mutator): DefinitionMutator
    {
        $mutator->setReplace('some/replace-2', 'self.version');
        $this->addToAssertionCount(1);

        return $mutator;
    }

    /**
     * @depends testSetReplace
     */
    public function testRemoveReplace(DefinitionMutator $mutator): DefinitionMutator
    {
        $mutator->removeReplace('some/replace');
        $this->addToAssertionCount(1);

        return $mutator;
    }

    /**
     * @depends testRemoveReplace
     */
    public function testSave(DefinitionMutator $mutator): void
    {
        $mutator->save();

        self::assertJsonFileEqualsJsonFile(__DIR__ . '/result_composer.json', __DIR__ . '/initial_composer.json');
    }

    public function testSaveException(): void
    {
        $mutator = $this->createMutator();
        $mutator->removeReplace('some/replace');
        unlink(__DIR__ . '/initial_composer.json');
        $this->expectException(MutatorException::class);
        $mutator->save();
    }

    public function testNotWriteableDirectoryOnSave(): void
    {
        $fileInfo = $this->createStub(SplFileInfo::class);
        $fileInfo->method('getContents')->willReturn('{}');
        $mutator = new DefinitionMutator($fileInfo);

        $mutator->setDependency('x', 'y');

        $this->expectException(MutatorException::class);
        $mutator->save();
    }

    public function testNotMutatedSave(): void
    {
        $mutator = $this->createMutator();
        unlink(__DIR__ . '/initial_composer.json');
        $mutator->save();
        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        copy(dirname(__DIR__, 2) . '/src/Resources/mutator/initial_composer.json', __DIR__ . '/initial_composer.json');
        copy(dirname(__DIR__, 2) . '/src/Resources/mutator/result_composer.json', __DIR__ . '/result_composer.json');
    }

    protected function tearDown(): void
    {
        if (file_exists(__DIR__ . '/initial_composer.json')) {
            unlink(__DIR__ . '/initial_composer.json');
        }

        if (file_exists(__DIR__ . '/result_composer.json')) {
            unlink(__DIR__ . '/result_composer.json');
        }
    }

    private function createMutator(): DefinitionMutator
    {
        return new DefinitionMutator(
            new SplFileInfo(
                __DIR__ . '/initial_composer.json',
                __DIR__,
                'initial_composer.json'
            )
        );
    }
}
