<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Rule;

use Nusje2000\ComposerMonolith\Helper\PackageFinder;
use Nusje2000\ComposerMonolith\Helper\PackageFinderInterface;
use Nusje2000\ComposerMonolith\NodeVisitor\ClassUseVisitor;
use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\Finder;
use UnexpectedValueException;

final class OutOfBoundsClassReferenceRule implements RuleInterface
{
    /**
     * @var PackageFinderInterface
     */
    private $packageFinder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(?LoggerInterface $logger = null, ?PackageFinderInterface $packageFinder = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->packageFinder = $packageFinder ?? new PackageFinder();
    }

    public function execute(DependencyGraph $graph): ViolationCollection
    {
        $violations = new ViolationCollection();

        $finder = Finder::create();
        $finder
            ->in($graph->getRootPackage()->getPackageLocation())
            ->notPath(['vendor/'])
            ->files()
            ->name('*.php');

        $traverser = new NodeTraverser();
        $visitor = new ClassUseVisitor($graph, $this->packageFinder);
        $traverser->addVisitor($visitor);

        $totalCount = $finder->count();
        $this->logger->info(sprintf('Starting evaluation of %d files.', $totalCount));

        $index = 1;
        foreach ($finder->getIterator() as $file) {
            $path = $file->getRealPath();
            if (false === $path) {
                throw new UnexpectedValueException('Could not resolve realpath.');
            }

            $this->logger->info(sprintf(
                'Evaluating "%s" for out of bound references to other packages (%d/%d).',
                $path,
                $index,
                $totalCount
            ));

            $source = $file->getContents();

            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $stmts = $parser->parse($source);
            $this->logger->debug(sprintf('Successfully parsed %s into an AST.', $path));

            $visitor->setCurrentFileName($path);
            $traverser->traverse($stmts);

            $fileViolations = $visitor->getOutOfBoundsViolations();
            $this->logger->info(sprintf(
                'Successfully traversed "%s", %d violations found (%d/%d).',
                $path,
                $fileViolations->count(),
                $index,
                $totalCount
            ));
            $violations->merge($fileViolations);

            $index++;
            $visitor->reset();
        }

        return $violations;
    }
}
