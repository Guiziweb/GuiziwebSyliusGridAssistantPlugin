<?php

namespace Guiziweb\SyliusGridAssistantPlugin\Util;

final class BrokenUtil
{
    public function compute(): int
    {
        return $undefinedVariable + 42;
    }
}