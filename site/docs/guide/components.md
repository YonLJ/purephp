# Components

A component is one file: a PHP function with typed parameters that returns
`Raw` markup, next to the template it renders. The file registers a lazy
factory, so `pure compile` can precompile the template while a request only
loads the artifact.

## Your First Component

```php
<?php

// components/Card.cmp.php — the component unit: function + template
use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h2(Slot::text('title')),
        p(Slot::text('content'))
    )->class('card')
));

function Card(string $title, string $content): Raw
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
register('Badge', __FILE__, static fn (): Shape => Compile::shape(
    span(Slot::text('label'))->class(Slot::attr('class'))
));

function Badge(string $label, string $class = 'badge'): Raw
{
    return render('Badge', label: $label, class: $class);
}
```

## Composing Components

A parent component calls its children and injects their output through
`Slot::raw`:

```php
<?php

// components/Button.cmp.php
register('Button', __FILE__, static fn (): Shape => Compile::shape(
    button(Slot::raw('icon'), Slot::text('label'))->class('btn')
));

function Button(Raw $icon, string $label): Raw
{
    return render('Button', icon: $icon, label: $label);
}

Button(Icon('#plus'), 'Add');
```

Lists work the same way: loop in the component function and pass the list of
child `Raw` values into a raw slot — it is stringified element by element and
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
register('Features', __FILE__, static fn (): Shape => Compile::shape(
    html(
        head(title(Slot::text('title'))),
        body(Slot::raw('content'))
    )
));

function featuresPage(array $data): Raw
{
    // The engine emits the tree as written; prepend the document header here.
    return Raw::of('<!DOCTYPE html>' . (string)render('Features',
        title: $data['title'],
        content: FeaturesBody($data['content']),
    ));
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
- `bind($source)` returns the `data → Raw` binder of a unit or template.

Use `bind()` when the template is an inline tree
(`bind(div(Slot::text('title')))`), or when you want to hold the binder in a
variable yourself:

```php
<?php

function Tag(string $label): Raw
{
    static $render;
    $render ??= bind(div(Slot::text('label'))->class('tag'));

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
`Slot::text()`, `Slot::attr()`, `Slot::raw()`, `Slot::each()`, `Slot::if()`,
`Slot::eachKind()` and `Slot::child()`. See [Props and Slots](/guide/props) for
the complete binding reference.

## Next Steps

- [Compiled Components](/guide/compiled) - Artifacts, caching and plain views
- [Props and Slots](/guide/props) - The complete data-binding reference
- [Events](/guide/events) - Event attributes and browser-side handlers
