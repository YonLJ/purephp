# Components

A component is one file: a PHP function that returns a `Pure\Component\Call`,
next to the template it renders and the typed props it accepts. The file
registers a lazy factory, so `pure compile` can precompile the template while a
request only loads the artifact.

## Your First Component

```php
<?php

// components/Card.cmp.php — the component unit: call function + template
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__,
    factory: static fn () => div(
        h2(Slot::value('title')),
        p(Slot::value('content'))
    )->class('card'),
    prepare: static function (string $title, string $content): array {
        return ['title' => $title, 'content' => $content];
    }
);

function Card(mixed ...$children): Call
{
    return component('Card', ...$children);
}

echo Card()->title('Title')->content('Content');
```

- `register()` stores the factory and the file; it builds nothing. A request
  that has a fresh artifact never calls the factory.
- `prepare()` is the typed prop contract: its parameters are the props, PHP
  enforces their types, and the array it returns is what binds the template.
- `Card()` returns a `Call`; props are set like tag attributes and the markup is
  produced on string conversion.
- Run `vendor/bin/pure compile components` to build `Card.pure.php` and (with
  `--plain`) `Card.plain.php` next to the unit. `pure compile --list` prints
  every unit it finds.

The registered name and the path of the unit file are interchangeable:
`component(__DIR__ . '/Card.cmp.php')` resolves to the same binder, so a
component can be called by name or by file.

## Props

Props are the parameters of the unit's `prepare()` hook: type them, give them
defaults, and return them into the template's slots. Values that never change
can be baked into the template; anything that changes per render belongs in the
bindings.

```php
<?php

// components/Badge.cmp.php
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\span;

register('Badge', __FILE__,
    factory: static fn () => span(Slot::value('label'))->class(Slot::value('class')),
    prepare: static function (string $label, string $class = 'badge'): array {
        return ['label' => $label, 'class' => $class];
    }
);

function Badge(mixed ...$children): Call
{
    return component('Badge', ...$children);
}

Badge()->label('Save')->class('badge');
```

## Fluent Calls

A component call reads like a tag: props are set with the same fluent setters,
children are passed to the call, and the result nests wherever a tag does.

```php
<?php

// components/Card.cmp.php — the same unit, called fluently
use Pure\Compile\Compile;
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{button, div, h2, li, ul};

register('Card', __FILE__,
    factory: static fn () => div(
        Slot::raw('children'),
        h2(Slot::value('type'))->class('card-title'),
        ul(Slot::each('features', li(Slot::value('value')))),
        button(Slot::value('text'))->class(Slot::value('class'))
    )->class('card'),
    prepare: static function (string $type, array $features, string $text, string $class): array {
        return ['type' => $type, 'features' => $features, 'text' => $text, 'class' => $class];
    }
);

function Card(mixed ...$children): Call
{
    return component('Card', ...$children);
}

echo div(
    Card(h2('Pro'))
        ->type('Free')
        ->features([['value' => '10 users'], ['value' => '2 GB']])
        ->text('Sign up for free')
        ->class('btn btn-lg btn-block btn-outline-primary')
);
```

- `component($name, ...$children)` returns a `Pure\Component\Call`, which
  implements `Pure\Core\Markup`: `div(Card(...))` emits it verbatim and renders
  it lazily with the tree, exactly like a tag child.
- Props bind slot names, so the template reads them with `Slot::value()`,
  `Slot::each()` or `Slot::child()`. `class()` and `style()` join their
  arguments exactly like the tag setters, and a `null` prop leaves the prop
  unset (the slot then reports itself as not provided, or falls back to its
  default).
- Children bind the reserved `children` slot: read it with
  `Slot::raw('children')`. A childless call renders it empty, and a call with
  children on a template that has no `children` slot throws.
- A prop the template does not read is reported by the development guard with a
  `did you mean` suggestion, and by `pure check` statically.
- A call function may type its props itself and return a `Call` — the call site
  is then checked by PHP, at the cost of writing the setters once:
  `function Badge(string $label): Call { return component('Badge')->label($label); }`

### Typed Props with prepare()

The fluent form passes props as data, so their types live in a `prepare`
closure instead of the call function. Its parameters are the prop contract —
PHP enforces the types, and a missing or unknown prop fails before rendering —
and the array it returns is what binds the template:

```php
<?php

register('Section', __FILE__,
    factory: static fn () => Compile::shape(...),
    prepare: static function (string $section, string $class, callable $item): array {
        $data = FeaturesService::section($section);

        return [
            'title' => $data['title'],
            'contents' => array_map(static fn (array $record): string => $item(...$record), $data['items']),
            'class' => $class,
        ];
    }
);

Section()->section('columns')->class('row g-4')->item(IconColumn(...));
```

Without a `prepare` closure the props are the bindings as they are, which fits
pure templates. `pure check` compares the `prepare()` parameters and the keys it
returns against the template's slots.

### Declared Props

A signature cannot state everything: which binding a prop fills when the names
differ, the shape of a list prop's items, or that a prop is on its way out. A
`#[Prop]` attribute states those facts, and `pure check` verifies them against
the template instead of inferring them:

```php
<?php

use Pure\Component\Prop;

register('Card', __FILE__,
    factory: static fn () => Compile::shape(...),
    prepare: static function (
        #[Prop(slot: 'title')] string $text,
        #[Prop(item: 'value')] array $features,
        #[Prop(required: false)] ?string $class = null,
        #[Prop(deprecated: 'use class()')] ?string $style = null,
    ): array {
        return ['title' => $text, 'features' => ..., 'class' => $class, 'style' => $style];
    }
);
```

- `slot` names the binding the prop fills; the parameter name is the default.
  When `prepare()` does not return one readable array literal — it builds the
  array in steps, or merges one — the declared slots are what the required slots
  of the template are checked against, instead of the checker going quiet.
- `item` names the single slot each item of a list prop fills in the item shape
  of a `Slot::each` slot, so the checker compares the two.
- `required` states the caller obligation; a declaration that contradicts the
  signature is reported.
- `deprecated` carries a migration hint: `pure check` prints it for every call
  site binding the prop, and the development guard warns at the call itself.

`#[Trusted]` marks a prop that carries already-rendered markup, so `pure check`
verifies it binds a raw slot — markup bound to a text slot would be escaped —
and the development guard warns when a call passes a value that is not
`Pure\Core\Markup`, which is where untrusted input reaches the output:

```php
prepare: static function (#[Trusted] Markup $icon): array
{
    return ['icon' => $icon];
}
```

`#[Binds]` declares the keys of a `prepare()` that builds its bindings in steps
or merges them from a service, so the required slots stay checked when the
returned array cannot be read:

```php
prepare: #[Binds('title', 'desc')] static function (): array
{
    return PricingService::pricing();
}
```

A page unit whose hook returns a `...bindings()` helper result declares the
keys the same way, on the hook itself:
`prepare: #[Binds('header', 'pricing')] static fn (): array => pricingBindings()`.
When a list prop is bound to an array literal at the call site, its item keys
are compared with the item shape of the slot — `->links([['txet' => '...']])` is
reported where it is written. An item shape that reads several slots needs no
declaration of its own: the nested shape is the contract.

Declarations are read by `pure check` and by the development guard; they are
never consulted while rendering, and a unit without them behaves exactly as
before.

A component call costs about two microseconds more than rendering a compiled
tree directly: the call object, the prop setters and the `prepare()` invocation.
The compiled artifact and the plain view are unaffected, and the benchmark in
`examples/bootstrap/bench.php` reports both paths.

## Composing Components

A component that wraps markup reads it from a raw `children` slot, and the
caller passes the children to the call — exactly like a tag:

```php
<?php

// components/Button.cmp.php
register('Button', __FILE__, static fn () =>
    button(Slot::raw('icon'), Slot::value('label'), Slot::raw('children'))->class('btn')
);

function Button(mixed ...$children): Call
{
    return component('Button', ...$children);
}

Button(Icon()->href('#plus'))->label('Add');
```

Lists work the same way: build the list of child calls (or rendered strings) in
the component's `prepare()` or at the call site and pass it into a raw slot — it
is stringified element by element and concatenated, so there is no `implode()`
to remember. Use `Slot::each()` inside
the template when the items are plain data rows that need no per-item component
logic.

## Pages

A page is a component unit whose root tag is a document root (`html`, `svg`,
`xml`, …). There is no separate page API: register it with `register()`, let its
`prepare()` hook supply the blocks, and render it with `component()`, then
prepend the document header of the root tag yourself — `<!DOCTYPE html>` for an
HTML root, the XML declaration for an XML or SVG one:

```php
<?php

// views/features.cmp.php
register('Features', __FILE__,
    factory: static fn () =>
        html(
            head(title(Slot::value('title'))),
            body(Slot::raw('content'))
        ),
    prepare: #[Binds('title', 'content')] static fn (): array => [
        // The page decides which blocks exist; each block fetches its own
        // records.
        'title' => FeaturesService::pageTitle(),
        'content' => FeaturesBody(),
    ]
);

function featuresPage(): string
{
    // The engine emits the tree as written; prepend the document header here.
    return '<!DOCTYPE html>' . component('Features')->render();
}
```

The call emits the tree as written, without a header, so a full page is the
document header of its root tag plus the rendered fragment.

`pure compile --plain` writes the same page as a dependency-free view file, so a
deployment without purephp can serve it; the controller prints the same
bindings either way.

## The Binder API

`component()` is a convenience over the lower-level helpers:

- `register($name, $file, $factory)` registers a unit under a name.
- `Registry::component($nameOrPath)` returns the `Closure(array $data): string`
  binder of a unit or shape file, to hold or pass around yourself.

For an inline tree, compile it once and keep the shape:

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;

function Tag(string $label): string
{
    static $render;
    $render ??= Compile::shape(div(Slot::value('label'))->class('tag'));

    return $render(['label' => $label]);
}
```

`Registry::component()` caches the binder per name or path, so you never need a
`static` variable for a registered unit.

## Caching

- A unit is served by its `*.pure.php` artifact when it is at least as new as
  the unit file; the factory and the shape tree are then never touched.
- `Registry::component()` caches the binder per name or path for the compile
  generation.
- `Compile::cachePath($dir)` — requests load generated renderers instead of
  regenerating them.
- `pure compile --check` keeps artifacts fresh in CI; a long-running worker
  keeps the loaded renderer in memory, so artifacts are optional there.

With opcache, requiring the artifacts of a whole page costs about half a
microsecond per component (see `bench/README.md`), so artifacts plus opcache are
the production path.

## Immediate Rendering (Snippets)

For one-off fragments you can skip shapes entirely and render a tag tree
directly:

```php
<?php

div(h2('Title'), p('Content'))->class('card')->print();
```

Use this for snippets and debugging only; production components should compile
a template so escaping and structure costs are paid once.

## Slot Reference

Components are functions; slots are the vocabulary *inside* a template:
`Slot::value()`, `Slot::raw()`, `Slot::child()`, `Slot::each()` and
`Slot::if()`. See [Props and Slots](/guide/props) for
the complete binding reference.

## Next Steps

- [Compiled Components](/guide/compiled) - Artifacts, caching and plain views
- [Props and Slots](/guide/props) - The complete data-binding reference
- [Events](/guide/events) - Event attributes and browser-side handlers
