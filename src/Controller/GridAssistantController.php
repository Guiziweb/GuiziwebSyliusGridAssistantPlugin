<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Controller;

use Guiziweb\SyliusGridAssistantPlugin\Context\GridContext;
use Guiziweb\SyliusGridAssistantPlugin\Form\Type\AiSearchType;
use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessor;
use Guiziweb\SyliusGridAssistantPlugin\Service\GridSchemaBuilder;
use Guiziweb\SyliusGridAssistantPlugin\Tool\FilterGridTool;
use Guiziweb\SyliusGridAssistantPlugin\Toolbox\FilterGridToolEnricher;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GridAssistantController extends AbstractController
{
    public function __construct(
        private readonly GridSchemaBuilder $schemaBuilder,
        private readonly GridQueryProcessor $queryProcessor,
        private readonly GridContext $gridContext,
        private readonly FilterGridToolEnricher $toolEnricher,
    ) {
    }

    #[Route(
        path: '/grid-assistant/search',
        name: 'guiziweb_grid_assistant_admin_search',
        methods: ['POST'],
    )]
    public function search(Request $request): Response
    {
        $form = $this->createForm(AiSearchType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                if ($error instanceof \Symfony\Component\Form\FormError) {
                    $errors[] = $error->getMessage();
                }
            }
            $this->addFlash('error', 'Invalid search request: ' . ($errors ? implode(', ', $errors) : 'form not submitted'));

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        /** @var array<string, mixed> $data */
        $data = $form->getData();
        $query = isset($data['query']) && is_string($data['query']) ? $data['query'] : '';
        $gridCode = isset($data['grid_code']) && is_string($data['grid_code']) ? $data['grid_code'] : '';
        $routeName = isset($data['route_name']) && is_string($data['route_name']) ? $data['route_name'] : '';
        $routeParamsRaw = isset($data['route_params']) && is_string($data['route_params']) ? $data['route_params'] : '{}';
        $routeParams = (array) (json_decode($routeParamsRaw, true) ?? []);

        if (empty($query) || empty($gridCode) || empty($routeName)) {
            $this->addFlash('error', 'Invalid search request.');

            return $this->redirectToRoute($routeName, $routeParams);
        }

        // Process the query with AI (grid existence is verified in processor)
        $result = $this->queryProcessor->process($query, $gridCode, $routeName, $routeParams);

        if (isset($result['error'])) {
            $this->addFlash('error', $result['error']);

            return $this->redirectToRoute($routeName, $routeParams);
        }

        // Show warnings if any
        if (isset($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                $this->addFlash('info', $warning);
            }
        }

        // Redirect to the filtered grid — redirect_url is guaranteed present since 'error' was not set
        $redirectUrl = $result['redirect_url'] ?? null;
        if (null === $redirectUrl) {
            $this->addFlash('error', 'No redirect URL generated.');

            return $this->redirectToRoute($routeName, $routeParams);
        }

        return $this->redirect($redirectUrl);
    }

    #[Route(
        path: '/grid-assistant/debug/{gridCode}',
        name: 'guiziweb_grid_assistant_admin_debug',
        methods: ['GET'],
    )]
    public function debug(string $gridCode): Response
    {
        if (!$this->schemaBuilder->gridExists($gridCode)) {
            return $this->json(['error' => 'Grid not found'], Response::HTTP_NOT_FOUND);
        }

        // Set context so the enricher can work
        $this->gridContext->setContext($gridCode, 'debug_route', []);

        // Create a dummy tool and enrich it to see the JSON Schema
        /** @var array{type: 'object', properties: array<string, array{type: string, description: string}>, required: array<int, string>, additionalProperties: false} $dummyParams */
        $dummyParams = ['type' => 'object', 'properties' => [], 'required' => [], 'additionalProperties' => false];
        $baseTool = new Tool(
            new ExecutionReference(FilterGridTool::class),
            'filter_grid',
            'Base description',
            $dummyParams,
        );

        $enrichedTool = $this->toolEnricher->enrich($baseTool);

        // Clear context
        $this->gridContext->clear();

        return $this->json([
            'enriched_tool' => [
                'name' => $enrichedTool->getName(),
                'description' => $enrichedTool->getDescription(),
                'parameters' => $enrichedTool->getParameters(),
            ],
        ], Response::HTTP_OK, [], ['json_encode_options' => \JSON_PRETTY_PRINT]);
    }
}
