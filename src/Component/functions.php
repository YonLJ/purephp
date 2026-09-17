<?php

declare(strict_types=1);

namespace Pure\Component;

use Closure;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ArtifactCompiler;
use Pure\Compile\Renderer;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Tag;
use RuntimeException;

/**
 * Bind a component template to a data → Raw function.
 *
 * A component is an ordinary function with typed parameters that returns Raw
 * markup; this helper turns its template into the renderer behind it. The
 * template is either a shape tree built inline or the path of a `*.shape.php`
 * file:
 *
 *     function Badge(string $label): Raw
 *     {
 *         static $render;
 *         $render ??= component(__DIR__ . '/Badge.shape.php');
 *
 *         return $render(['label' => $label]);
 *     }
 *
 * A shape file is loaded through its precompiled artifact when one exists next
 * to it and is at least as new as the shape file; otherwise the shape file is
 * compiled (the disk cache still applies). Run `pure compile` to build
 * artifacts and `pure compile --check` to keep them fresh in CI.
 *
 * @param Tag|string $source A shape tree, or the path of a `*.shape.php` file.
 * @return Closure(array<string, mixed>): Raw The data → Raw binder.
 */
function component(Tag|string $source): Closure
{
    return binder($source, false);
}

/**
 * Bind a page template to a data → Raw function, including the document
 * header of its root tag (the `<!DOCTYPE html>` of an `html()` root).
 *
 * @param Tag|string $source A shape tree, or the path of a `*.shape.php` file.
 * @return Closure(array<string, mixed>): Raw The data → Raw binder.
 */
function page(Tag|string $source): Closure
{
    return binder($source, true);
}

/**
 * Render a component template in one expression: the binder of the template
 * is cached per path, so a component function is a single return statement.
 *
 *     function Section(string $title, Raw $contents, string $class): Raw
 *     {
 *         return render(
 *             __DIR__ . '/Section.shape.php',
 *             title: $title,
 *             contents: $contents,
 *             class: $class
 *         );
 *     }
 *
 * Slot values are passed as named arguments, or as an unpacked array with
 * string keys: `render($file, ...$bindings)`.
 *
 * @param string $source The path of a `*.shape.php` file.
 * @param mixed ...$data The slot values, by slot name.
 * @return Raw The rendered markup.
 */
function render(string $source, mixed ...$data): Raw
{
    if ($data !== [] && array_is_list($data)) {
        throw new RuntimeException(
            "component '{$source}': slot values must be passed by name, e.g. render(\$file, title: \$title)."
        );
    }

    static $binders = [];

    return ($binders[$source] ??= component($source))($data);
}

/**
 * Render a page template in one expression, including the document header of
 * its root tag. The binder is cached per path.
 *
 * @param string $source The path of a `*.shape.php` file.
 * @param array<string, mixed> $data The slot values, by slot name.
 * @return Raw The rendered document.
 */
function renderPage(string $source, array $data): Raw
{
    static $binders = [];

    return ($binders[$source] ??= page($source))($data);
}

/**
 * @param Tag|string $source A shape tree, or the path of a `*.shape.php` file.
 * @param bool $document Whether the binder prepends the document header.
 * @return Closure(array<string, mixed>): Raw
 */
function binder(Tag|string $source, bool $document): Closure
{
    if ($source instanceof Tag) {
        $renderer = Compile::shape($source)->compile();
        $header = $source->documentHeader();
    } else {
        [$renderer, $header] = load($source);
    }

    return static fn (array $data): Raw => Raw::of(
        ($document ? $header : '') . $renderer->render($data)
    );
}

/**
 * Load the renderer of a component template: the precompiled artifact when it
 * is fresh, the compiled shape file otherwise.
 *
 * @param string $shapeFile The path of a `*.shape.php` file.
 * @return array{0: Renderer, 1: string} The renderer and the document header.
 */
function load(string $shapeFile): array
{
    if (!is_file($shapeFile)) {
        throw new RuntimeException("component template '{$shapeFile}' does not exist.");
    }

    $artifact = ArtifactCompiler::artifactPath($shapeFile);
    $artifactTime = is_file($artifact) ? filemtime($artifact) : false;
    $shapeTime = filemtime($shapeFile);

    if ($artifactTime !== false && $shapeTime !== false && $artifactTime >= $shapeTime) {
        $renderer = require $artifact;

        if (!$renderer instanceof Renderer) {
            throw new RuntimeException(
                "component artifact '{$artifact}' must return a Renderer; run `pure compile`."
            );
        }

        return [$renderer, $renderer->header];
    }

    $shape = require $shapeFile;

    if (!$shape instanceof Shape) {
        throw new RuntimeException("component template '{$shapeFile}' must return a Shape.");
    }

    return [$shape->compile(), $shape->tree()->documentHeader()];
}
