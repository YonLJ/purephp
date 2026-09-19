<?php

declare(strict_types=1);

namespace Pure\Component;

use Closure;
use RuntimeException;

/**
 * Register a component unit under a name.
 *
 * The factory must be lazy: it is stored as-is and only called when no fresh
 * artifact serves the unit. A unit file registers one component and defines
 * its function next to the call:
 *
 *     register('Icon', __FILE__, static fn () => svg(
 *         svgUse()->href(Slot::value('href'))
 *     ));
 *
 *     function Icon(string $href): string
 *     {
 *         return render('Icon', href: $href);
 *     }
 *
 * @param string $name The component name used by render().
 * @param string $file The unit file, normally `__FILE__`.
 * @param Closure(): mixed $factory Builds the template lazily; may return a tag tree or a Shape.
 * @param bool $override Replace an existing registration of the name or file.
 * @return void
 */
function register(string $name, string $file, Closure $factory, bool $override = false): void
{
    Registry::register($name, $file, $factory, $override);
}

/**
 * Render a component template in one expression: the binder is cached per name
 * or path, so a component function is a single return statement.
 *
 *     function Section(string $title, iterable|string $contents, string $class): string
 *     {
 *         return render('Section', title: $title, contents: $contents, class: $class);
 *     }
 *
 * Slot values are passed as named arguments, or as an unpacked array with
 * string keys: `render($file, ...$bindings)`. A slot value may be a string or
 * any Stringable: it is coerced to a string at render time.
 *
 * The rendered markup is the tree as written, with no document header. To
 * emit a full document, prepend the header of the root tag yourself, e.g.
 * `$html->documentHeader() . render('Page', ...$data)`.
 *
 * @param string $source A registered name or the path of a `*.shape.php` file.
 * @param mixed ...$data The slot values, by slot name.
 * @return string The rendered markup.
 */
function render(string $source, mixed ...$data): string
{
    if ($data !== [] && array_is_list($data)) {
        throw new RuntimeException(
            "component '{$source}': slot values must be passed by name, e.g. render(\$file, title: \$title)."
        );
    }

    return Registry::component($source)($data);
}
