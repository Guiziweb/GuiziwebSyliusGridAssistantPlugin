# Usage

## Where the assistant appears

On every grid listed in `config/packages/guiziweb_sylius_grid_assistant.yaml`, a search bar appears at the top of the index page (injected via the Twig hook `sylius_admin.common.index.content.grid`).

Disabled by default - opt-in explicitly:

```yaml
guiziweb_sylius_grid_assistant:
    enabled_grids:
        - sylius_admin_order
        - sylius_admin_product
```

## How to use as an admin

1. Go to an index page of an enabled grid (e.g. `/admin/orders`)
2. Type your query in plain language in the assistant search bar
3. Click "Search"
4. The grid reloads with filters and sorting applied. The redirect URL contains `?criteria[...]=...&sorting[...]=...&ai_query=...`

If your query is too vague or doesn't match any available filter/field, an error message is shown above the grid.

## Example queries

```
orders over $100 from john.doe@gmail.com last month
paid orders sorted by date desc
products in stock, cheapest first
new orders only
```

## What's supported

- **Filter types**: `boolean`, `date`, `entity`, `enum`, `exists`, `money`, `numeric_range`, `resource_autocomplete`, `select`, `string`, `ux_autocomplete`, `ux_translatable_autocomplete` (12 Sylius native types covered by 8 built-in schema builders)
- **Sorting**: any field marked `sortable:` in your Sylius grid definition
- **Languages**: the system prompt is in English but the LLM understands queries in most languages (quality varies)

## Hiding a filter or field from the AI

You may want a filter visible to humans but invisible to the AI (internal-only fields, technical columns, ...). Add `ai_searchable: false` in the filter or field options **directly in your Sylius grid definition** (not in the plugin config):

```yaml
sylius_grid:
    grids:
        sylius_admin_order:
            filters:
                customer:
                    type: string
                    # ai_searchable: true is the default, no need to set it
                internal_notes:
                    type: string
                    options:
                        ai_searchable: false   # hidden from AI
            fields:
                number:
                    type: string
                    sortable: ~
                internal_id:
                    type: integer
                    sortable: internalId
                    options:
                        ai_searchable: false   # hidden from AI sorting
```

The filter/field still works for human admins; only the AI ignores it.

## Rate limiting

Each authenticated admin user is limited to **10 queries per minute** (fixed window, per user). Exceeded users get a "rate limit exceeded, retry in N seconds" error.

This limit is hardcoded in the bundle's `prepend()` (no plugin config option). To customize, override the rate limiter in your own `framework.yaml`:

```yaml
framework:
    rate_limiter:
        guiziweb_grid_assistant:
            policy: fixed_window
            limit: 30
            interval: '1 minute'
```

## What it doesn't do

- No direct SQL queries - everything goes through Sylius grid filters
- No aggregations (sums, counts, averages) - filtering and sorting only
- No cross-grid navigation
- No data modifications (no "cancel all pending orders" or similar)

The assistant translates natural language to filter/sort URL params, then redirects. Anything beyond that is out of scope.

## Error messages

- **"No AI platform configured..."**: you installed the plugin but haven't run `composer require symfony/ai-bundle symfony/ai-XXX-platform`. See [Installation](installation.md).
- **"AI processing failed. Please try again."**: runtime error from the LLM (timeout, quota, malformed response). Check your provider status.
- **"Could not determine any filter from your query..."**: the LLM couldn't match your query to available filters. Try rephrasing.
- **"Rate limit exceeded, retry in N seconds"**: you've hit the 10/min limit. Wait.