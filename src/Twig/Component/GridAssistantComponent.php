<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Twig\Component;

use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessorException;
use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessorInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GridAssistantComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'guiziweb.grid_assistant.query_not_blank')]
    #[Assert\Length(max: 500, maxMessage: 'guiziweb.grid_assistant.query_too_long')]
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
        private readonly GridQueryProcessorInterface $queryProcessor,
        private readonly TranslatorInterface $translator,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[LiveAction]
    public function search(): ?RedirectResponse
    {
        $this->error = null;

        $violations = $this->validator->validate($this);
        if (0 !== $violations->count()) {
            $violation = $violations->get(0);
            $this->error = $this->translator->trans((string) $violation->getMessage(), $violation->getParameters());

            return null;
        }

        /** @var array<string, mixed> $routeParams */
        $routeParams = (array) (json_decode($this->routeParams, true) ?? []);

        try {
            $redirectUrl = $this->queryProcessor->process(
                $this->query,
                $this->gridCode,
                $this->routeName,
                $routeParams,
            );
        } catch (GridQueryProcessorException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        $redirectUrl .= (str_contains($redirectUrl, '?') ? '&' : '?') . 'ai_query=' . urlencode($this->query);

        return new RedirectResponse($redirectUrl);
    }
}
