<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Filter\StringFilter;

final class StringOperators
{
    public const ALL = [
        StringFilter::TYPE_EQUAL,
        StringFilter::TYPE_NOT_EQUAL,
        StringFilter::TYPE_CONTAINS,
        StringFilter::TYPE_NOT_CONTAINS,
        StringFilter::TYPE_STARTS_WITH,
        StringFilter::TYPE_ENDS_WITH,
        StringFilter::TYPE_EMPTY,
        StringFilter::TYPE_NOT_EMPTY,
        StringFilter::TYPE_IN,
        StringFilter::TYPE_NOT_IN,
    ];
}
