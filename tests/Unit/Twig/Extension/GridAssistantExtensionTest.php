<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Twig\Extension;

use Guiziweb\SyliusGridAssistantPlugin\Twig\Extension\GridAssistantExtension;
use PHPUnit\Framework\TestCase;

final class GridAssistantExtensionTest extends TestCase
{
    public function testIsEnabledReturnsTrueForGridInTheEnabledList(): void
    {
        $extension = new GridAssistantExtension(['sylius_admin_order', 'sylius_admin_customer']);

        self::assertTrue($extension->isEnabled('sylius_admin_order'));
        self::assertTrue($extension->isEnabled('sylius_admin_customer'));
    }

    public function testIsEnabledReturnsFalseForGridNotInTheEnabledList(): void
    {
        $extension = new GridAssistantExtension(['sylius_admin_order']);

        self::assertFalse($extension->isEnabled('sylius_admin_product'));
    }

    public function testIsEnabledReturnsFalseWhenNoGridIsEnabled(): void
    {
        $extension = new GridAssistantExtension([]);

        self::assertFalse($extension->isEnabled('sylius_admin_order'));
    }
}