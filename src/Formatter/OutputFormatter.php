<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Formatter;

use Symfony\Component\Console\Formatter\OutputFormatter as BaseOutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

final class OutputFormatter extends BaseOutputFormatter
{
    public function __construct(bool $decorated = true, array $styles = [])
    {
        parent::__construct($decorated, $styles);

        $this->setStyle('error', new OutputFormatterStyle('red'));
        $this->setStyle('success', new OutputFormatterStyle('green'));
        $this->setStyle('dependency', new OutputFormatterStyle('blue'));
        $this->setStyle('package', new OutputFormatterStyle('blue'));
        $this->setStyle('file', new OutputFormatterStyle('blue'));
        $this->setStyle('version', new OutputFormatterStyle('magenta'));
        $this->setStyle('rule', new OutputFormatterStyle('blue'));
        $this->setStyle('violation', new OutputFormatterStyle('red'));
    }
}
