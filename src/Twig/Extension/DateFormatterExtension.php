<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\DateFormatterExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class DateFormatterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_formatter', [DateFormatterExtensionRuntime::class, 'format']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('date_formatter', [DateFormatterExtensionRuntime::class, 'format']),
        ];
    }
}
