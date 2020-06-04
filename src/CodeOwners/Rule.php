<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\CodeOwners;

final class Rule
{
    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $filePatern;

    public function __construct(string $user, string $filePatern)
    {
        $this->user = $user;
        $this->filePatern = $filePatern;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getFilePatern(): string
    {
        return $this->filePatern;
    }

    public function toString(): string
    {
        return sprintf('%s %s', $this->getFilePatern(), $this->getUser());
    }
}
