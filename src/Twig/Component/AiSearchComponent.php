<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Twig\Component;

use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessor;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'guiziweb:grid_assistant:ai_search',
    template: '@GuiziwebSyliusGridAssistantPlugin/components/ai_search.html.twig'
)]
final class AiSearchComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp]
    public string $gridCode = '';

    #[LiveProp]
    public string $routeName = '';

    #[LiveProp]
    public string $routeParams = '{}';

    #[LiveProp]
    public ?string $error = null;

    public function __construct(
        private readonly GridQueryProcessor $queryProcessor,
    ) {
    }

    #[LiveAction]
    public function search(): ?RedirectResponse
    {
        $this->error = null;

        if (empty(trim($this->query))) {
            $this->error = 'Please enter a search query.';

            return null;
        }

        $routeParams = json_decode($this->routeParams, true) ?? [];

        $result = $this->queryProcessor->process(
            $this->query,
            $this->gridCode,
            $this->routeName,
            $routeParams
        );

        if (isset($result['error'])) {
            $this->error = $result['error'];

            return null;
        }

        // Build redirect URL
        $redirectUrl = $result['redirect_url'];
        $redirectUrl .= (str_contains($redirectUrl, '?') ? '&' : '?') . 'ai_query=' . urlencode($this->query);

        // Add warnings to URL if any (will be displayed via flash message)
        if (isset($result['warnings']) && is_array($result['warnings']) && !empty($result['warnings'])) {
            $redirectUrl .= '&ai_warning=' . urlencode(implode('. ', $result['warnings']));
        }

        return new RedirectResponse($redirectUrl);
    }
}