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
 * its call function next to it:
 *
 *     register('Icon', __FILE__, static fn () => svg(
 *         svgUse()->href(Slot::value('href'))
 *     ));
 *
 *     function Icon(mixed ...$children): Call
 *     {
 *         return component('Icon', ...$children);
 *     }
 *
 * A unit may also register a `prepare` closure: the typed props-to-bindings
 * hook of its fluent call. The closure's parameters are the prop contract, so
 * PHP enforces their types and a missing or unknown prop fails before render:
 *
 *     register('Section', __FILE__,
 *         factory: static fn () => Compile::shape(...),
 *         prepare: static function (string $section, string $class, callable $item): array {
 *             return ['title' => ..., 'contents' => ..., 'class' => $class];
 *         });
 *
 * @param string $name The component name used by render() and component().
 * @param string $file The unit file, normally `__FILE__`.
 * @param Closure(): mixed $factory Builds the template lazily; may return a tag tree or a Shape.
 * @param bool $override Replace an existing registration of the name or file.
 * @param ?Closure $prepare Optional props-to-bindings hook for fluent calls.
 * @return void
 */
function register(string $name, string $file, Closure $factory, bool $override = false, ?Closure $prepare = null): void
{
    Registry::register($name, $file, $factory, $override, $prepare);
}

/**
 * Start a fluent component call: props are set like tag attributes, children
 * are passed to the call, and the markup is produced on string conversion.
 *
 *     echo Card(h2('Pro'))
 *         ->type('Free')
 *         ->price('0')
 *         ->features(['10 users included', '2 GB of storage'])
 *         ->text('Sign up for free')
 *         ->class('btn btn-lg btn-block btn-outline-primary');
 *
 * The call implements Pure\Core\Markup, so it nests like a tag:
 * `div(Card(...)->type('Free'))`. A prop named after a slot binds that slot;
 * children bind the reserved `children` slot, which the template reads with
 * `Slot::raw('children')`. `render('Card', ...)` remains the low-level entry
 * and behaves identically.
 *
 * @param string $name A registered component name or a template path.
 * @param mixed ...$children The children of the call, as tag children.
 * @return Call The call object.
 */
function component(string $name, mixed ...$children): Call
{
    return new Call($name, array_values($children));
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
 * A slot error is prefixed with the component name or template path
 * (`component 'Card': slot 'title' is required ...`), so the failing unit is
 * identifiable from the message alone.
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
