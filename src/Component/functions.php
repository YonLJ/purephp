<?php

declare(strict_types=1);

namespace Pure\Component;

use Closure;
use Pure\Compile\Compile;
use Pure\Core\Raw;
use Pure\Core\Tag;
use RuntimeException;

/**
 * Register a component unit under a name.
 *
 * The factory must be lazy: it is stored as-is and only called when no fresh
 * artifact serves the unit. A unit file registers one component and defines
 * its function next to the call:
 *
 *     register('Icon', __FILE__, static fn (): Shape => Compile::shape(
 *         svg(svgUse()->href(Slot::attr('href')))
 *     ));
 *
 *     function Icon(string $href): Raw
 *     {
 *         return render('Icon', href: $href);
 *     }
 *
 * @param string $name The component name used by render().
 * @param string $file The unit file, normally `__FILE__`.
 * @param Closure(): mixed $factory Builds the template lazily; must return a Shape.
 * @param bool $override Replace an existing registration of the name or file.
 * @return void
 */
function register(string $name, string $file, Closure $factory, bool $override = false): void
{
    Registry::register($name, $file, $factory, false, $override);
}

/**
 * Register a page unit under a name: like register(), but the binder prepends
 * the document header of the root tag (the `<!DOCTYPE html>` of an `html()`
 * root).
 *
 * @param string $name The page name used by renderPage().
 * @param string $file The unit file, normally `__FILE__`.
 * @param Closure(): mixed $factory Builds the template lazily; must return a Shape.
 * @param bool $override Replace an existing registration of the name or file.
 * @return void
 */
function registerPage(string $name, string $file, Closure $factory, bool $override = false): void
{
    Registry::register($name, $file, $factory, true, $override);
}

/**
 * Bind a component template to a data → Raw function.
 *
 * A component is an ordinary function with typed parameters that returns Raw
 * markup; this helper turns its template into the renderer behind it. The
 * template is a shape tree built inline, the name of a registered unit, or the
 * path of a `*.shape.php` file:
 *
 *     function Badge(string $label): Raw
 *     {
 *         return render('Badge', label: $label);
 *     }
 *
 * A registered unit is served by its precompiled artifact when one exists next
 * to it and is at least as new as the unit file; only otherwise is its factory
 * called and the shape compiled (the disk cache still applies). Run
 * `pure compile` to build artifacts and `pure compile --check` to keep them
 * fresh in CI.
 *
 * @param Tag|string $source A shape tree, a registered name, or a template path.
 * @return Closure(array<int|string, mixed>): Raw The data → Raw binder.
 */
function component(Tag|string $source): Closure
{
    return binder($source, false);
}

/**
 * Bind a page template to a data → Raw function, including the document
 * header of its root tag (the `<!DOCTYPE html>` of an `html()` root).
 *
 * @param Tag|string $source A shape tree, a registered name, or a template path.
 * @return Closure(array<int|string, mixed>): Raw The data → Raw binder.
 */
function page(Tag|string $source): Closure
{
    return binder($source, true);
}

/**
 * Render a component template in one expression: the binder is cached per name
 * or path, so a component function is a single return statement.
 *
 *     function Section(string $title, Raw $contents, string $class): Raw
 *     {
 *         return render('Section', title: $title, contents: $contents, class: $class);
 *     }
 *
 * Slot values are passed as named arguments, or as an unpacked array with
 * string keys: `render($file, ...$bindings)`. A slot value may be a string or
 * any Stringable (including a Raw returned by another component): it is
 * coerced to a string at render time, so a Raw is passed as-is without a
 * `(string)` cast.
 *
 * @param string $source A registered name or the path of a `*.shape.php` file.
 * @param mixed ...$data The slot values, by slot name: strings, or Raw / Stringable markup.
 * @return Raw The rendered markup.
 */
function render(string $source, mixed ...$data): Raw
{
    if ($data !== [] && array_is_list($data)) {
        throw new RuntimeException(
            "component '{$source}': slot values must be passed by name, e.g. render(\$file, title: \$title)."
        );
    }

    return Registry::component($source)($data);
}

/**
 * Render a page template in one expression, including the document header of
 * its root tag.
 *
 * @param string $source A registered page name or the path of a `*.shape.php` file.
 * @param array<string, mixed> $data The slot values, by slot name: strings, or Raw / Stringable markup.
 * @return Raw The rendered document.
 */
function renderPage(string $source, array $data): Raw
{
    return Registry::page($source)($data);
}

/**
 * @param Tag|string $source A shape tree, a registered name, or a template path.
 * @param bool $document Whether the binder prepends the document header.
 * @return Closure(array<int|string, mixed>): Raw
 */
function binder(Tag|string $source, bool $document): Closure
{
    if ($source instanceof Tag) {
        $renderer = Compile::shape($source)->compile();
        $header = $source->documentHeader();

        $binder =
            /** @param array<string, mixed> $data */
            static fn (array $data): Raw => Raw::of(
                ($document ? $header : '') . $renderer->render($data)
            );

        return $binder;
    }

    return $document ? Registry::page($source) : Registry::component($source);
}
