<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Controller;

use Guiziweb\SyliusGridAssistantPlugin\Context\GridContext;
use Guiziweb\SyliusGridAssistantPlugin\Form\Type\AiSearchType;
use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessor;
use Guiziweb\SyliusGridAssistantPlugin\Service\GridSchemaBuilder;
use Guiziweb\SyliusGridAssistantPlugin\Toolbox\FilterGridToolEnricher;
use Guiziweb\SyliusGridAssistantPlugin\Tool\FilterGridTool;
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
        methods: ['POST']
    )]
    public function search(Request $request): Response
    {
        $form = $this->createForm(AiSearchType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Invalid search request: ' . ($errors ? implode(', ', $errors) : 'form not submitted'));

            return $this->redirect($request->headers->get('referer', '/admin'));
        }

        $data = $form->getData();
        $query = $data['query'] ?? '';
        $gridCode = $data['grid_code'] ?? '';
        $routeName = $data['route_name'] ?? '';
        $routeParams = json_decode($data['route_params'] ?? '{}', true) ?? [];

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
        if (isset($result['warnings']) && is_array($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                $this->addFlash('info', $warning);
            }
        }

        // Redirect to the filtered grid
        return $this->redirect($result['redirect_url']);
    }

    #[Route(
        path: '/grid-assistant/debug/{gridCode}',
        name: 'guiziweb_grid_assistant_admin_debug',
        methods: ['GET']
    )]
    public function debug(string $gridCode): Response
    {
        if (!$this->schemaBuilder->gridExists($gridCode)) {
            return $this->json(['error' => 'Grid not found'], Response::HTTP_NOT_FOUND);
        }

        // Set context so the enricher can work
        $this->gridContext->setContext($gridCode, 'debug_route', []);

        // Create a dummy tool and enrich it to see the JSON Schema
        $baseTool = new Tool(
            new ExecutionReference(FilterGridTool::class),
            'filter_grid',
            'Base description',
            ['type' => 'object', 'properties' => []],
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
