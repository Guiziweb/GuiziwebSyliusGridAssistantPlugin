# Installation

This plugin is **provider-agnostic** - you choose which LLM bridge (OpenAI, Gemini, Anthropic, Mistral, ...) you want to use.

## Prerequisites

- PHP `^8.2`
- Sylius `^2.0`
- An AI provider account + API key

## 1. Add the Guiziweb Flex recipe endpoint (one time per project)

This step is optional - it tells Symfony Flex to fetch our recipe so the install is auto-configured. Add to your project's `composer.json`:

```json
{
    "extra": {
        "symfony": {
            "endpoint": [
                "https://api.github.com/repos/Guiziweb/SyliusRecipes/contents/index.json?ref=flex/main",
                "flex://defaults"
            ]
        }
    }
}
```

See [Guiziweb/SyliusRecipes](https://github.com/Guiziweb/SyliusRecipes) for details.

If you skip this step, jump to [Manual install](#manual-install) below.

## 2. Install the plugin

```bash
composer require guiziweb/sylius-grid-assistant-plugin
```

The Flex recipe will:
- Register `GuiziwebSyliusGridAssistantPlugin` in `config/bundles.php`
- Create `config/packages/guiziweb_sylius_grid_assistant.yaml`
- Print the next steps

## 3. Install an AI bridge

Pick one provider and install its bridge:

```bash
# OpenAI
composer require symfony/ai-bundle symfony/ai-open-ai-platform

# Google Gemini
composer require symfony/ai-bundle symfony/ai-gemini-platform

# Anthropic Claude
composer require symfony/ai-bundle symfony/ai-anthropic-platform

# Mistral
composer require symfony/ai-bundle symfony/ai-mistral-platform
```

See [Providers](providers.md) for comparison and cost notes.

## 4. Configure the AI platform

If you have Flex enabled (the endpoint added at step 1, or any Flex-enabled project), **this step is automatic**. The bridge's recipe creates the config for you:

- `symfony/ai-open-ai-platform` creates `config/packages/ai_open_ai_platform.yaml` with the OpenAI config and appends `OPENAI_API_KEY=` to your `.env`
- Similarly for the other bridges

If you skipped step 1 (no Flex), manually create `config/packages/ai.yaml`:

```yaml
ai:
    platform:
        openai:
            api_key: '%env(OPENAI_API_KEY)%'
```

Replace `openai` with the provider you installed (`gemini`, `anthropic`, `mistral`).

## 5. Add your API key

In `.env.local` (this file is gitignored, never commit it):

```
OPENAI_API_KEY=sk-...
```

## 6. Enable the assistant on the grids you want

Edit `config/packages/guiziweb_sylius_grid_assistant.yaml`:

```yaml
guiziweb_sylius_grid_assistant:
    enabled_grids:
        - sylius_admin_order
        - sylius_admin_product
        - sylius_admin_customer
```

Use the Sylius grid codes. By default no grid is enabled - you opt in explicitly.

## 7. Clear the cache

```bash
bin/console cache:clear
```

Visit any enabled admin index page (e.g. `/admin/orders`) - the AI assistant search bar appears at the top.

> For details on each AI bridge (models, options), see the [Symfony AI bridges documentation](https://github.com/symfony/ai).

## Manual install

If you skipped step 1 (no Flex recipe endpoint), do the work yourself:

1. Run the same `composer require` commands as steps 2 and 3
2. Add to `config/bundles.php`:
   ```php
   Symfony\AI\AiBundle\AiBundle::class => ['all' => true],
   Guiziweb\SyliusGridAssistantPlugin\GuiziwebSyliusGridAssistantPlugin::class => ['all' => true],
   ```
3. Create `config/packages/guiziweb_sylius_grid_assistant.yaml`:
   ```yaml
   imports:
       - { resource: "@GuiziwebSyliusGridAssistantPlugin/config/config.yaml" }

   guiziweb_sylius_grid_assistant:
       enabled_grids:
           - sylius_admin_order
   ```
4. Continue with steps 4-7 above.