# Components

A component is a PHP function with typed parameters that returns `Raw` markup.
The template behind it is a shape file that `pure compile` precompiles, and the
`render()` helper binds the two in a single expression.

## Your First Component

```php
<?php

// components/Card.shape.php — the template: static markup plus slots
return Compile::shape(
    div(
        h2(Slot::text('title')),
        p(Slot::text('content'))
    )->class('card')
);
```

```php
<?php

// components/Card.php — the component: typed props in, Raw markup out
use Pure\Core\Raw;

use function Pure\Component\render;

function Card(string $title, string $content): Raw
{
    return render(
        __DIR__ . '/Card.shape.php',
        title: $title,
        content: $content
    );
}

echo Card('Title', 'Content');
```

`render()` binds the shape file to a `data → Raw` function and caches that
binder per path, so the template is loaded once per process: it uses the
sibling `Card.pure.php` artifact when it is fresh and compiles
`Card.shape.php` otherwise. Slot values are passed by name, or as an unpacked
array with string keys (`render($file, ...$bindings)`). Run
`vendor/bin/pure compile components` to build the artifacts.

## Props

Props are function parameters: type them, give them defaults, and pass them
into the template's slots. Values that never change can be baked into the
template; anything that changes per render belongs in the bindings.

```php
<?php

function Badge(string $label, string $class = 'badge'): Raw
{
    return render(__DIR__ . '/Badge.shape.php', label: $label, class: $class);
}
```

```php
<?php

// components/Badge.shape.php
return Compile::shape(
    span(Slot::text('label'))->class(Slot::attr('class'))
);
```

## Composing Components

A parent component calls its children and injects their output through
`Slot::raw`:

```php
<?php

// components/Button.shape.php
return Compile::shape(
    button(Slot::raw('icon'), Slot::text('label'))->class('btn')
);

// components/Button.php
function Button(Raw $icon, string $label): Raw
{
    return render(__DIR__ . '/Button.shape.php', icon: $icon, label: $label);
}

Button(Icon('#plus'), 'Add');
```

Lists work the same way: loop in the component function, join the markup, pass
the string into a raw slot. Use `Slot::each()` inside the template when the
items are plain data rows that need no per-item component logic.

## The Binder API

`render()` is a convenience over two lower-level helpers:

- `component($source)` returns the `data → Raw` binder of a template.
- `page($source)` does the same and prepends the document header of the root
  tag (the `<!DOCTYPE html>` of an `html()` root).

Use them when the template is an inline tree (`component(div(Slot::text('title')))`)
or when you want to hold the binder in a variable yourself:

```php
<?php

function Tag(string $label): Raw
{
    static $render;
    $render ??= component(div(Slot::text('label'))->class('tag'));

    return $render(['label' => $label]);
}
```

`render($file, ...)` and `renderPage($file, ...)` are the one-expression forms
for shape files; both cache the binder per path, so you never need a `static`
variable for a file-backed component.

## Pages

`renderPage()` is `render()` plus the document header:

```php
<?php

function featuresPage(array $data): Raw
{
    return renderPage(__DIR__ . '/features.shape.php', [
        'title' => $data['title'],
        'content' => (string) FeaturesBody($data['content']),
    ]);
}
```

A page template is a shape file like any other, so pages get artifacts too; the
controller just prints the result. The plain flavor (`pure compile --plain`)
prints the same bindings from a dependency-free view file.

## Caching

- `render()` / `renderPage()` — the binder is cached per template path, so the
  artifact or shape is loaded once per process.
- `Compile::cachePath($dir)` — requests load generated renderers instead of
  regenerating them.
- `pure compile` artifacts — the binder skips the shape tree and the
  fingerprint when the artifact is fresh; `pure compile --check` keeps CI
  honest.
- Long-running workers keep the memoized renderer in memory, so artifacts are
  optional there.

See [Compiled Components](/guide/compiled) for the full caching story.

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
