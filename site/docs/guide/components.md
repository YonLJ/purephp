# Components

A component is one file: a PHP function with typed parameters that returns
`string`, next to the template it renders. The file registers a lazy
factory, so `pure compile` can precompile the template while a request only
loads the artifact.

## Your First Component

```php
<?php

// components/Card.cmp.php — the component unit: function + template
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn () =>
    div(
        h2(Slot::value('title')),
        p(Slot::value('content'))
    )->class('card')
);

function Card(string $title, string $content): string
{
    return render('Card', title: $title, content: $content);
}

echo Card('Title', 'Content');
```

- `register()` stores the factory and the file; it builds nothing. A request
  that has a fresh artifact never calls the factory.
- `render('Card', ...)` renders the registered template, passing slot values by
  name (or as an unpacked string-keyed array: `render('Card', ...$bindings)`).
- Run `vendor/bin/pure compile components` to build `Card.pure.php` and (with
  `--plain`) `Card.plain.php` next to the unit. `pure compile --list` prints
  every unit it finds.

The registered name and the path of the unit file are interchangeable:
`render(__DIR__ . '/Card.cmp.php', ...)` resolves to the same binder, so a
component can be rendered by name or by file.

## Props

Props are function parameters: type them, give them defaults, and pass them
into the template's slots. Values that never change can be baked into the
template; anything that changes per render belongs in the bindings.

```php
<?php

// components/Badge.cmp.php
register('Badge', __FILE__, static fn () =>
    span(Slot::value('label'))->class(Slot::value('class'))
);

function Badge(string $label, string $class = 'badge'): string
{
    return render('Badge', label: $label, class: $class);
}
```

[Fluent calls](#fluent-calls) carry the same props as setters instead
(`Badge('Save')->label('Save')->class('badge')`), with a `prepare()` closure as
the typed contract.

## Fluent Calls

A component call can read like a tag: props are set with the same fluent
setters, children are passed to the call, and the result nests wherever a tag
does.

```php
<?php

// components/Card.cmp.php — the same unit, called fluently
use Pure\Compile\Compile;
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{button, div, h2, li, ul};

register('Card', __FILE__, static fn () => div(
    Slot::raw('children'),
    h2(Slot::value('type'))->class('card-title'),
    ul(Slot::each('features', li(Slot::value('value')))),
    button(Slot::value('text'))->class(Slot::value('class'))
)->class('card'));

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
- `render('Card', ...)` stays the low-level entry point; both forms resolve the
  same binder, artifacts, cache and errors.

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

## Composing Components

A parent component calls its children and injects their output through
`Slot::raw`:

```php
<?php

// components/Button.cmp.php
register('Button', __FILE__, static fn () =>
    button(Slot::raw('icon'), Slot::value('label'))->class('btn')
);

function Button(iterable|string $icon, string $label): string
{
    return render('Button', icon: $icon, label: $label);
}

Button(Icon('#plus'), 'Add');
```

Lists work the same way: loop in the component function and pass the list of
child rendered strings into a raw slot — it is stringified element by element and
concatenated, so there is no `implode()` to remember. Use `Slot::each()` inside
the template when the items are plain data rows that need no per-item component
logic.

## Pages

A page is a component unit whose root tag is a document root (`html`, `svg`,
`xml`, …). There is no separate page API: register it with `register()` and
render it with `render()`, then prepend the document header of the root tag
yourself — `<!DOCTYPE html>` for an HTML root, the XML declaration for an XML
or SVG one:

```php
<?php

// views/features.cmp.php
register('Features', __FILE__, static fn () =>
    html(
        head(title(Slot::value('title'))),
        body(Slot::raw('content'))
    )
);

function featuresPage(): string
{
    // The engine emits the tree as written; prepend the document header here.
    // The page decides which blocks exist, each block fetches its own records.
    return '<!DOCTYPE html>' . render('Features',
        title: FeaturesService::pageTitle(),
        content: FeaturesBody(),
    );
}
```

`render()` emits the tree as written, without a header, so a full page is the
document header of its root tag plus the rendered fragment.

`pure compile --plain` writes the same page as a dependency-free view file, so a
deployment without purephp can serve it; the controller prints the same
bindings either way.

## The Binder API

`render()` is a convenience over the lower-level helpers:

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

`render()` caches the binder per name or path, so you never need a `static`
variable for a registered unit.

## Caching

- A unit is served by its `*.pure.php` artifact when it is at least as new as
  the unit file; the factory and the shape tree are then never touched.
- `render()` caches the binder per name or path for the compile
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
