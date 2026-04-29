<?php

declare(strict_types=1);

namespace Tests\Guiziweb\SyliusGridAssistantPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Webmozart\Assert\Assert;

final class GridAssistantContext extends RawMinkContext implements Context
{
    /**
     * @When I search for :query using the AI assistant
     */
    public function iSearchForUsingTheAiAssistant(string $query): void
    {
        $page = $this->getSession()->getPage();

        $input = $page->find('css', 'input[name="query"]');
        Assert::notNull($input, 'AI search input not found on the page.');
        $input->setValue($query);

        $form = $page->find('css', 'form[data-live-action-param="search"]');
        Assert::notNull($form, 'AI search form not found on the page.');
        $button = $form->find('css', 'button[type="submit"]');
        Assert::notNull($button, 'AI search submit button not found.');
        $button->click();

        $this->getSession()->wait(60000, 'document.readyState === "complete" && window.location.href.includes("criteria")');
    }
}