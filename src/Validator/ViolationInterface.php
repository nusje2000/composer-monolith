<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator;

interface ViolationInterface
{
    public function getMessage(): string;

    public function getFormattedMessage(): string;
}
