<?php

declare(strict_types=1);

namespace Pure\Component;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;

/**
 * Register a component unit under a name.
 *
 * The factory must be lazy: it is stored as-is and only called when no fresh
 * artifact serves the unit. A unit file registers one component and defines
 * its call function next to it. The preferred form passes the call function
 * itself, so the component name exists exactly once in the file — as the
 * function name — and neither the name nor the file can drift apart:
 *
 *     function Icon(mixed ...$children): Call
 *     {
 *         return component(__FUNCTION__, ...$children);
 *     }
 *
 *     register(Icon(...), static fn () => svg(
 *         svgUse()->href(Slot::value('href'))
 *     ));
 *
 * The classic form stays supported: `register('Icon', __FILE__, ...)`, with
 * `component('Icon', ...)` inside the call function; `pure check` and the
 * PHPStan rule keep both forms honest. Because the file derives from the call
 * function, the factory may be passed second:
 *
 *     register(Icon(...), static fn () => svg(...));
 *
 * A unit may also register a `prepare` closure: the typed props-to-bindings
 * hook of its fluent call. The closure's parameters are the prop contract, so
 * PHP enforces their types and a missing or unknown prop fails before render:
 *
 *     register(Section(...),
 *         factory: static fn () => Compile::shape(...),
 *         prepare: static function (string $section, string $class, callable $item): array {
 *             return ['title' => ..., 'contents' => ..., 'class' => $class];
 *         });
 *
 * @param Closure|string $name The call function as `Icon(...)`, or the component name used by component().
 * @param string|Closure $file The unit file (normally `__FILE__`), or — with a call function as $name — the factory itself.
 * @param Closure(): mixed|null $factory Builds the template lazily; may return a tag tree or a Shape.
 * @param bool $override Replace an existing registration of the name or file.
 * @param ?Closure $prepare Optional props-to-bindings hook for fluent calls.
 * @return void
 */
function register(Closure|string $name, string|Closure $file = '', ?Closure $factory = null, bool $override = false, ?Closure $prepare = null): void
{
    if ($file instanceof Closure) {
        if ($factory !== null) {
            throw new InvalidArgumentException('register() received two factories; pass exactly one factory closure.');
        }

        $factory = $file;
        $file = '';
    }

    if ($name instanceof Closure) {
        $reflection = new ReflectionFunction($name);

        if ($reflection->getName() === '{closure}') {
            throw new InvalidArgumentException('register() takes the call function as Icon(...); pass the named function, not an anonymous closure.');
        }

        $name = $reflection->getName();
        $file = $file !== '' ? $file : (string) ($reflection->getFileName() ?: '');
    }

    if ($factory === null) {
        throw new InvalidArgumentException("register() needs a factory closure for component '{$name}'.");
    }

    if ($file === '') {
        throw new InvalidArgumentException("register() needs the unit file (normally __FILE__), unless the first argument is the call function as '{$name}(...)'.");
    }

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
 * `Slot::raw('children')`.
 *
 * Inside the unit's own call function the name carries no literal:
 * `return component(__FUNCTION__, ...$children);`.
 *
 * @param string $name A registered component name or a template path.
 * @param mixed ...$children The children of the call, as tag children.
 * @return Call The call object.
 */
function component(string $name, mixed ...$children): Call
{
    return new Call($name, array_values($children));
}
