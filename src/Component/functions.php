<?php

declare(strict_types=1);

namespace Pure\Component;

use Closure;

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
 * @param string $name The component name used by component().
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
 * `Slot::raw('children')`.
 *
 * @param string $name A registered component name or a template path.
 * @param mixed ...$children The children of the call, as tag children.
 * @return Call The call object.
 */
function component(string $name, mixed ...$children): Call
{
    return new Call($name, array_values($children));
}
