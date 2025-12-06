# Configuration

This plugin requires `symfony/ai-bundle` and an AI platform to be configured.

## AI Configuration

Create `config/packages/ai.yaml`:

```yaml
ai:
    platform:
        # Option 1: Anthropic (Claude)
        anthropic:
            api_key: '%env(ANTHROPIC_API_KEY)%'

        # Option 2: OpenAI
        # openai:
        #     api_key: '%env(OPENAI_API_KEY)%'

    agent:
        # The agent must be named 'grid_assistant'
        grid_assistant:
            platform: ai.platform.anthropic  # or ai.platform.openai
            model: claude-sonnet-4-20250514       # or gpt-4o for OpenAI
            tools:
                services:
                    - Guiziweb\SyliusGridAssistantPlugin\Tool\FilterGridTool
```

## Environment Variables

Add to your `.env` file:

```
ANTHROPIC_API_KEY=your_api_key_here
# or
OPENAI_API_KEY=your_api_key_here
```

## Twig Hook Integration

The plugin adds a search component via Twig hooks. It automatically appears on grid pages.

To customize which grids show the AI search, configure the twig hooks in your project.