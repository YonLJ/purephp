# Compiled Components

Compiled rendering turns a data-free **shape** into a flat PHP renderer.
Static markup is escaped once at compile time and emitted as a literal string,
so rendering a page costs little more than string concatenation plus escaping
of the dynamic values — at parity with compiled template engines.

Shapes are built **once per process** — a long-running worker, a preloaded or
CLI process, or any runtime that keeps PHP state between requests. Under
standard PHP-FPM every request starts fresh, so enable the on-disk cache
(see [Caching](#caching)) to load compiled renderers instead of regenerating
them per request.

## Shape, Slot, Renderer

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, li, ul};

// A shape is a normal tag tree with Slot placeholders instead of data.
$item = Compile::shape(li(Slot::text('title')));

$page = Compile::shape(
    div(
        h1(Slot::text('heading')),
        ul(Slot::each('items', $item))
    )->class('card')
);

// Rendering binds plain data.
echo $page([
    'heading' => 'Users',
    'items' => [['title' => 'Ada'], ['title' => 'Grace']],
]);
```

Output is **byte-identical** to `Tag::render()` for the same tree, because both
paths share the same escaping implementation.

| Object | Meaning |
| --- | --- |
| `Shape` | A data-free tree; `__invoke($data)`, `compile()`, `id()`, `print($data)`, `save($path, $data)` |
| `Renderer` | The compiled renderer; `render($data)`, `save($path, $data)`, and the readonly `source` / `id` properties |
| `Slot` | A placeholder for data, bound at render time |

## Slot Types

| Constructor | Value | Behavior |
| --- | --- | --- |
| `Slot::text($name)` | stringable or `null` | coerced to string, escaped; `null` renders empty content |
| `Slot::attr($name)` | stringable or `null` | escaped attribute value; `null` omits the attribute (same as `setAttr(null)`) |
| `Slot::raw($name)` | stringable or `null` | emitted verbatim, never escaped |
| `Slot::child($name, $shape)` | array | nested data scope for `$shape` |
| `Slot::each($name, $shape)` | iterable of arrays | renders `$shape` for every item |
| `Slot::if($name, $then, $else = null)` | truthy check | renders `$then` when `$data[$name]` is truthy, otherwise `$else`; a missing key is false and never throws |
| `Slot::eachKind($name, ['kind' => $shape, ...])` | iterable of arrays | dispatches every item on `$item['kind']`; an unknown kind throws an `InvalidArgumentException` |

Modifiers:

- `->required(false)` — the slot may be missing.
- `->default($value)` — fallback used when the key is missing.
- `Slot::if()` rejects both modifiers with a `LogicException`.
- `Slot::child(..., $map)` / `Slot::each(..., $map)` / `Slot::eachKind(..., $map)` — derive
  the nested scope with a closure instead of reading `$data[$name]`; this is how a
  component maps its own props to a child component.

Value coercion: `null`, scalars and `Stringable` are accepted for
text/attribute/raw slots; arrays and other objects raise an
`InvalidArgumentException` naming the full slot path.

## Scope and Missing Data

`Slot::child()` and `Slot::each()` create a nested data scope; inside it, slots
resolve against that scope. Missing required keys throw
`Pure\Core\MissingSlotException` with the full path, for example
`slot 'items[].title' is required but was not provided.` Use `default()` or
`required(false)` for optional data.

`Slot::if()` and `Slot::eachKind()` branches share the current scope, so this
works naturally:

```php
$item = Compile::shape(
    li(
        Slot::text('name'),
        Slot::if('admin', Compile::shape(span('(admin)')))
    )
);
```

## Components

A component is a function that returns a `Shape`. Static props are function
arguments, dynamic props are slots, and the shape is memoized in `static` so it
is compiled once per process (see [Caching](#caching) for the PHP-FPM case):

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, p};

function CardShape(string $classList = 'card'): Shape
{
    static $shapes = [];

    return $shapes[$classList] ??= Compile::shape(
        div(
            h2(Slot::text('title')),
            p(Slot::text('content'))
        )->class($classList)
    );
}

function PageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            Slot::child('card', CardShape('card shadow'))
        )->class('container')
    );
}

PageShape()->print([
    'card' => ['title' => 'Hello', 'content' => 'Compiled card'],
]);
```

Nested components use `Slot::child()`, lists use `Slot::each()`, mixed lists use
`Slot::eachKind()`, and optional/conditional markup uses `Slot::if()`.

### Lists

```php
$row = Compile::shape(li(Slot::text('label')));

$shape = Compile::shape(ul(Slot::each('rows', $row)));
$shape(['rows' => [['label' => 'a'], ['label' => 'b']]]);
```

### Heterogeneous lists

```php
$text = Compile::shape(p(Slot::text('value')));
$link = Compile::shape(a(Slot::text('value'))->href(Slot::attr('href')));

$shape = Compile::shape(div(Slot::eachKind('blocks', [
    'text' => $text,
    'link' => $link,
])));

$shape(['blocks' => [
    ['kind' => 'text', 'value' => 'hello'],
    ['kind' => 'link', 'value' => 'docs', 'href' => '/docs'],
]]);
```

Every item must be an array carrying the discriminator key (`kind` by default;
pass a different key as the third argument of `Slot::eachKind()`).

## Caching

By default compiled renderers live in memory only, which pays off in
long-running workers that keep state between requests. Under standard PHP-FPM
the shape tree is rebuilt and the renderer regenerated on every request — that
is slower than immediate rendering — so opt in to the on-disk renderer cache,
which loads generated code instead of regenerating it:

```php
use Pure\Compile\Compile;

// Once during bootstrap
Compile::cachePath(__DIR__ . '/var/cache/purephp');
```

- Cache files are content-addressed by `Shape::id()`; a changed shape
  produces a new file.
- Writes are atomic (temp file + rename), so concurrent workers are safe.
- Cache files are plain PHP and opcache-friendly. The directory must be
  private: owned by the PHP user, not writable by group or others (0700 is
  created when missing), and outside the web root — `cachePath()` rejects loose
  or foreign-owned directories. Do not point it at a shared location such as
  `/tmp` itself.
- `Compile::clearCache()` deletes the files written by the library.
- `Compile::flush()` invalidates in-memory renderers (useful in long-running
  workers after a deploy).

To catch shapes that are rebuilt per request (instead of being memoized),
enable the development guard:

```php
Compile::guard(true);           // or set PURE_COMPILE_GUARD=1
```

When the same call site calls `Compile::shape()` too many times in one process,
PHP emits an `E_USER_WARNING` suggesting the `static $shape ??=` pattern.

## Performance

Measured on PHP 8.4 (604-element page, 200 rows; reproduce with
`php bench/compare.php`):

| Path | Time per render |
| --- | --- |
| build tree + `render()` | ~700–750 µs |
| render only (same tree reused) | ~220–230 µs |
| compiled shape + data | ~120 µs |
| compiled static tree (literal) | < 1 µs |

The bootstrap features example renders about 10× faster with the compiled
path. Reproduce them with:

```bash
php bench/compare.php
php examples/bootstrap-features/bench.php
```

## Limitations

- Tag names cannot depend on data: a shape always uses the same tags. Use
  `Slot::if()` / `Slot::eachKind()` for structural variation, or normalize the
  data before rendering.
- Compiled code is tied to the shape structure; changing a shape changes its
  `id()` and therefore its cache file.
- Map closures are fingerprinted by file and line; changing a closure body in
  place does not change the fingerprint. Clear the cache (or bump
  `Compile::CACHE_VERSION`) when you edit map closures.
- A shape tree is read live while it compiles, and `id()` reflects the tree as
  it is at that moment. An already compiled renderer keeps rendering the tree
  state it was built from, so call `Compile::flush()` after mutating a tree that
  is already wrapped in a shape; building shapes once per process avoids this
  entirely.
- Shapes must not contain request data — they are process-level artifacts.

## Classic Component → Shape Mapping

| Classic component | Compiled component |
| --- | --- |
| `function Card(array $props): HTML` | `function CardShape(): Shape` |
| `h2($title)` | `h2(Slot::text('title'))` |
| `->class($classList)` | `->class($classList)` for static props, `->class(Slot::attr('classList'))` for dynamic ones |
| `array_map(fn ($row) => Row($row), $rows)` | `Slot::each('rows', RowShape())` |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | `Slot::child('props', ChildShape())` or a map |

Immediate (`render()`) tag trees remain available for snippets and debugging;
see [Basic Usage](/guide/basic-usage).
