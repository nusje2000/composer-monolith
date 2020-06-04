<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\CodeOwners;

use Nusje2000\ComposerMonolith\CodeOwners\Builder;
use Nusje2000\DependencyGraph\Author\AuthorCollection;
use Nusje2000\DependencyGraph\Author\AuthorInterface;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use PHPUnit\Framework\TestCase;

final class BuilderTest extends TestCase
{
    public function testBuildFromGraph(): void
    {
        $dependencyGraph = new DependencyGraph('/test', new PackageCollection([
            $this->createPackage('/test', false, $this->createAuthor('bill', 'bill@coding.com')),
            $this->createPackage('/test/sub-package-1', false, $this->createAuthor('henk', 'henk@coding.com')),
            $this->createPackage('/test/sub-package-2', false, $this->createAuthor('bob', 'bob@coding.com'), $this->createAuthor('chap', null)),
            $this->createPackage('/test/vendor/package', true, $this->createAuthor('bob', 'bob@coding.com')),
            $this->createPackage('/test/sub-package-3', false, $this->createAuthor('henk', 'henk@coding.com'), $this->createAuthor('dani', 'dani@coding.com')),
        ]));

        $file = Builder::fromGraph($dependencyGraph);
        self::assertSame(implode(PHP_EOL, [
            '/ bill@coding.com',
            '/sub-package-1/ henk@coding.com',
            '/sub-package-2/ bob@coding.com',
            '/sub-package-3/ henk@coding.com',
            '/sub-package-3/ dani@coding.com',
        ]), $file->toString());
    }

    private function createPackage(string $location, bool $isVendor, AuthorInterface ...$authors): PackageInterface
    {
        $package = $this->createStub(PackageInterface::class);
        $package->method('getPackageLocation')->willReturn($location);
        $package->method('isFromVendor')->willReturn($isVendor);
        $package->method('getAuthors')->willReturn(new AuthorCollection($authors));

        return $package;
    }

    private function createAuthor(string $name, ?string $email): AuthorInterface
    {
        $author = $this->createStub(AuthorInterface::class);
        $author->method('getName')->willReturn($name);
        $author->method('getEmail')->willReturn($email);

        return $author;
    }
}
