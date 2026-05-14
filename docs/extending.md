# Extending

The plugin's 8 built-in schema builders cover 10 native Sylius filter types out of the box: `boolean`, `date`, `entity`, `enum`, `exists`, `money`, `numeric_range`, `select`, `string`, `ux_autocomplete`, `ux_translatable_autocomplete`. If your project defines **a custom filter type** outside that list, you need to teach the assistant about it.

## When you need to extend

Only when you have a **custom Sylius filter type**. If you're using stock Sylius filters with custom options/configurations, the existing builders already handle them.

## Scope: this plugin vs Sylius

This guide covers **the plugin's side** - the SchemaBuilder/ValueFormatter pair that teaches the AI about your filter. Registering the filter type with Sylius itself is **not** part of this plugin and must be done separately:

1. A PHP class implementing `Sylius\Component\Grid\Filtering\FilterInterface`
2. A service tagged `sylius.grid_filter` with both `type` and `form_type` attributes
3. A template registered in `sylius_grid.templates.filter` (otherwise Sylius admin sidebar throws "Missing template for filter type X")

See the [Sylius Grid Bundle documentation](https://docs.sylius.com/en/latest/book/grids/grids.html) for these steps. Once your filter is wired with Sylius, the rest of this guide tells you how to teach the AI about it.

## Two interfaces to implement

For each custom filter type, you need to provide:

1. **A SchemaBuilder** - describes the filter to the LLM (JSON Schema describing the shape of the value the AI should return)
2. **A ValueFormatter** - converts the LLM's response into the format your Sylius filter expects

Both are auto-registered via Symfony service autoconfiguration - just create the classes, no service tag or YAML needed.

## Example: custom `tags` filter

Say your Sylius project has a custom filter named `tags` (matching a Sylius `TagsFilter::NAME`), which expects a comma-separated string and matches products by tag.

### 1. SchemaBuilder

```php
<?php

declare(strict_types=1);

namespace App\GridAssistant\Schema;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\FilterSchemaBuilderInterface;
use Sylius\Component\Grid\Definition\Filter;

final class TagsFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    public static function getType(): string
    {
        return 'tags';
    }

    public function build(Filter $filter): array
    {
        return [
            'type' => 'string',
            'description' => sprintf(
                '%s - comma-separated list of tags. Omit if not mentioned by the user.',
                (string) $filter->getLabel(),
            ),
        ];
    }
}
```

The `build()` method returns a JSON Schema fragment that becomes part of the `criteria.<filter_name>` shape in the LLM `response_format` parameter.

### 2. ValueFormatter

```php
<?php

declare(strict_types=1);

namespace App\GridAssistant\Schema;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterFormatResult;
use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterValueFormatterInterface;
use Sylius\Component\Grid\Definition\Filter;

final class TagsFilterValueFormatter implements FilterValueFormatterInterface
{
    public static function getType(): string
    {
        return 'tags';
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        if (!is_string($value) || '' === trim($value)) {
            return new FilterFormatResult(null); // ignore this criterion
        }

        return new FilterFormatResult(trim($value));
    }
}
```

The `format()` method receives the raw value from the LLM (whatever the schema returned by `build()` allows) and returns a `FilterFormatResult` containing the value in the shape Sylius expects.

Return `new FilterFormatResult(null)` to silently drop the criterion (e.g. empty value, invalid format).

## That's it

No service registration. The plugin auto-discovers any class implementing `FilterSchemaBuilderInterface` or `FilterValueFormatterInterface` and registers them with the proper tags.

## Testing your extension

1. **Check the container**: `bin/console lint:container` should pass.
2. **Test with a query**: in the admin, use a query that should trigger your filter (e.g. `"products tagged limited-edition"` for a `tags` filter).
3. **Inspect the URL after redirect**: it should include `?criteria[tags]=limited-edition`.
4. **Look at the AI log**: by default the plugin logs to the `ai` channel - search for `[GridAssistant]` entries to see what filters/sorting the LLM produced.

## Multi-type builders/formatters

If your class handles several filter types (rare), `getType()` can return an array:

```php
public static function getType(): array
{
    return ['tags', 'labels'];
}
```

The same builder/formatter will be used for both types.

## Custom rate-limit key

By default, the plugin rate-limits AI queries per authenticated admin user (the `UserIdentifierKeyResolver`, which throws when no user is logged in). To use a different key (IP, tenant, anything else), implement `RateLimitKeyResolverInterface` and alias it in your `services.yaml`.

```php
<?php

declare(strict_types=1);

namespace App\GridAssistant;

use Guiziweb\SyliusGridAssistantPlugin\RateLimiter\RateLimitKeyResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class IpKeyResolver implements RateLimitKeyResolverInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function resolve(): string
    {
        return $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'anonymous';
    }
}
```

```yaml
# config/services.yaml
services:
    Guiziweb\SyliusGridAssistantPlugin\RateLimiter\RateLimitKeyResolverInterface:
        alias: App\GridAssistant\IpKeyResolver
```

The resolver may throw `Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessorException` to reject the request before any AI call is made (that's how the default resolver handles anonymous users).

## Overriding a built-in type

Overriding a native builder/formatter for one of the stock types is technically possible (the registry stores them by type, so any same-type registration replaces the previous one), **but the registration order isn't guaranteed** by Symfony's autoconfiguration. If you really need to override, the safest path is to disable the plugin's built-in service in your `services.yaml` (turning off autoconfigure removes the tag that would register it as a builder) and replace it explicitly:

```yaml
services:
    Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\StringFilterSchemaBuilder:
        autoconfigure: false  # disables the auto-tag, so it's no longer registered as a builder

    App\GridAssistant\Schema\MyStringFilterSchemaBuilder: ~
```

Most users don't need this - prefer extending behavior via filter options (`ai_searchable`, custom `formOptions`) before resorting to overriding.