# Core Concepts

This guide explains the core concepts of PurePHP.

## Choosing a Path

One rule covers both paths:

- **Data drives the output → slots and shapes**: replace the values with `Slot`
  placeholders, wrap the tree in `Compile::shape()` and bind plain data per
  request.
- **A snippet, prototype or debug output → render immediately**: build the tree
  with the real values and call `print()` or `render()`.

| Action | `Tag` (immediate) | `Shape` (compiled) |
| --- | --- | --- |
| String | `render()` | `$shape($data)` |
| Output | `print()` | `print($data)` |
| File | `save($path)` | `save($path, $data)` |
| Debug | `toJSON()` | `compile()->source` |

Each name exists once: `render()` returns a string, `print()` echoes, `save()`
writes a file (prepending the document header of the root tag) and `toJSON()` /
`source` expose the structure for debugging.

## Tag Trees

PurePHP represents HTML with PHP objects. Tag helper functions build a tree, and
method chaining sets attributes:

```php
<?php

use function Pure\HTML\{div, h1, p};

$element = div(
    h1('Title'),
    p('Content')
)->class('container');

echo $element; // <div class="container"><h1>Title</h1><p>Content</p></div>
```

Text children and attribute values are escaped while rendering; `Raw` children
are emitted verbatim. A tag tree that contains data is rendered immediately
with `render()`, `print()` or `__toString()`. That path is the right tool for
snippets and debugging.

## Shapes and Slots

A **shape** is the same kind of tree, but *data-free*: dynamic values are
replaced by `Slot` placeholders. A shape describes structure; data arrives
later.

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, p};

$shape = Compile::shape(
    div(
        h1(Slot::value('heading')),
        p(Slot::value('lead'))
    )->class('container')
);
```

Slot types:

| Slot | Binds | Creates a nested scope |
| --- | --- | --- |
| `Slot::value()` | scalar / `null` / `Stringable` | no — child position escapes to text; attribute position follows `setAttr()` semantics (`true`→`name="name"`, `false`/`null` omitted) |
| `Slot::raw()` | stringable value (or a list of them), verbatim | no |
| `Slot::child()` | array | yes |
| `Slot::each()` | iterable of arrays | yes, per item |
| `Slot::if()` | truthy condition | no (branches share the scope) |

A slot name is always the **data key** (and the error path), never a tag or
attribute name: in `a(Slot::value('label'))->class(Slot::value('classList'))` the
text binds `label` and the class attribute binds `classList`, while `a` and
`class` come from the tree.

## Compiling

`Compile::shape()` wraps a tree as a `Shape`. The first render compiles it into
a flat PHP closure: static markup becomes a literal string, escaping is done
once, and only slots remain as runtime work.

```php
<?php

$shape([
    'heading' => 'Welcome',
    'lead' => 'Compiled rendering',
]);
```

Key properties:

- **Compiled once per process** — memoize shapes in `static` variables inside
  component functions, never build them inside a request handler. Under
  standard PHP-FPM statics reset every request, so enable
  `Compile::cachePath()` to load compiled renderers instead of regenerating
  them.
- **Byte-identical output** — the compiled path and `render()` share the same
  escaping implementation.
- **Optional disk cache** — `Compile::cachePath($dir)` stores compiled
  renderers so warm workers load code instead of generating it.

`Shape::id()` is a structural fingerprint (tags, attributes, slots and nested
shapes) that is available without compiling; it names the on-disk cache file, and
a precompiled artifact records it so `pure compile --check` can tell a stale one.
Components are resolved by their registered name or unit file, not by it.

## Data Binding and Scope

Rendering a shape binds plain data:

```php
<?php

$list = Compile::shape(ul(Slot::each('items', li(Slot::value('title')))));

$list(['items' => [['title' => 'a'], ['title' => 'b']]]);
```

`Slot::child()` and `Slot::each()` establish a nested scope, so inside `li` the
slot `title` resolves against the current item. Missing required keys throw
`Pure\Core\MissingSlotException` with the full path
(`slot 'items[].title' is required but was not provided.`), and the message
suggests the closest provided key or lists the keys the scope did provide; a
required value or raw slot bound to an explicit `null` fails with
`slot 'items[].title' is required but was null.` Use `->default($value)` or
`->required(false)` for optional data.

## Components

A component is one `*.cmp.php` unit: a function with typed parameters returning
`string`, plus the lazy factory registered next to it:

```php
<?php

// components/Card.cmp.php
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn () =>
    div(
        h2(Slot::value('title')),
        p(Slot::value('content'))
    )->class(Slot::value('class'))
);

function Card(string $title, string $content, string $class = 'card'): string
{
    return render('Card', title: $title, content: $content, class: $class);
}
```

See [Components](/guide/components) for composition and
[Compiled Components](/guide/compiled) for artifacts, caching and the
per-request guard.

## State Management

State is plain PHP: values are passed into the shape as data.

### Simple State

```php
<?php


use function Pure\HTML\{button, div, p};
use function Pure\Component\render;

function Counter(int $count): string
{
    return render('Counter', count: $count);
}

echo Counter(0);
```

### Global State

Any PHP store works; assemble the data array and render:

```php
<?php

class Store
{
    private static array $state = [];

    public static function set(string $key, mixed $value): void
    {
        self::$state[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        return self::$state[$key] ?? null;
    }
}

Store::set('user', ['name' => 'John']);
$user = Store::get('user');
```

## Conditional Lists

The main table above covers the everyday slots. A list whose items need
different markup is dispatched in the data layer: render each item through the
component function that fits it and pass the joined markup into a raw slot.

```php
function Blocks(array $blocks): string
{
    $html = '';

    foreach ($blocks as $block) {
        $html .= $block['kind'] === 'link'
            ? LinkBlock($block['value'], $block['href'])
            : TextBlock($block['value']);
    }

    return $html;
}

$blocks = Compile::shape(div(Slot::raw('blocks')));
$blocks(['blocks' => Blocks($blocks)]);
```

See [Mixed lists](/guide/compiled#mixed-lists) in the compiled guide for the
full treatment.

## Next Steps

- [Compiled Components](/guide/compiled) - The production rendering path
- [Props and Slots](/guide/props) - How data is bound to shapes
- [Basic Usage](/guide/basic-usage) - The tag API used by snippets
