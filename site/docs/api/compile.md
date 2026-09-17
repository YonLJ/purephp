# Compiled Rendering

`Pure\Compile\Compile` compiles a data-free **shape** tree into a flat PHP
renderer. Static markup is escaped once at compile time and emitted as literal
string chunks, so rendering a page costs little more than string concatenation
plus escaping of the dynamic values.

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, li, p, ul};

// build + compile once per process
$item  = Compile::shape(li(Slot::text('title')));
$shape = Compile::shape(
    div(
        h1(Slot::text('heading')),
        ul(Slot::each('items', $item))
    )->class('card')
);

// render per request with plain data
echo $shape([
    'heading' => 'Users',
    'items' => [['title' => 'Ada'], ['title' => 'Grace']],
]);
```

Output is **byte-identical** to `Tag::render()` for the same tree, because both
paths share the same escaping implementation (`Pure\Core\Escaper`, `@internal`).

## Classes

| Class | Purpose |
| --- | --- |
| `Pure\Compile\Compile` | Facade: `shape()`, `cachePath()`, `clearCache()`, `flush()`, `guard()` |
| `Pure\Compile\Shape` | A data-free tree: `__invoke($data)`, `compile()`, `id()`, `print($data)`, `save($path, $data)` |
| `Pure\Compile\Renderer` | The compiled renderer: `render($data)`, `save($path, $data, $header = null)` and the readonly `source` / `id` properties |
| `Pure\Core\Slot` | Placeholder constructors (`text`, `attr`, `raw`, `child`, `each`, `if`, `eachKind`) and modifiers |
| `Pure\Core\MissingSlotException` | Thrown when a required slot is missing, with the full path |

## Shape vs. Data

A shape is a normal tag tree in which dynamic values are replaced by `Slot`
placeholders. Shapes must not contain request data, and must be built **once
per process** — put them in a `static` variable inside a component function,
never inside a request handler.

| Classic component | Compiled component |
| --- | --- |
| `function Card(array $props): HTML` | `function CardShape(): Shape` |
| `h2($title)` | `h2(Slot::text('title'))` |
| `->class($classList)` | `->class($classList)` for static props, `->class(Slot::attr('classList'))` for dynamic ones |
| `array_map(fn ($row) => Row($row), $rows)` | `Slot::each('rows', RowShape())` |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | `Slot::child('props', ChildShape())` or a component map |

Static child components need no slot at all — build them inside the shape and
they are compiled into literals:

```php
$shape = Compile::shape(div(Header(), Slot::each('rows', $row))->class('page'));
```

## Slot Types

| Constructor | Value | Behavior |
| --- | --- | --- |
| `Slot::text($name)` | stringable or `null` | coerced to string, escaped; `null` renders as empty content |
| `Slot::attr($name)` | stringable or `null` | escaped attribute value; `null` omits the attribute (same as `setAttr(null)`) |
| `Slot::raw($name)` | stringable or `null` | emitted verbatim, never escaped |
| `Slot::child($name, $shape)` | array | nested data scope for `$shape` |
| `Slot::each($name, $shape)` | iterable of arrays | renders `$shape` for every item |
| `Slot::if($name, $then, $else = null)` | truthy check | renders `$then` when `$data[$name]` is truthy, otherwise `$else`; a missing key is false and never throws |
| `Slot::eachKind($name, ['kind' => $shape], $kindKey = 'kind')` | iterable of arrays | dispatches each item on `$item[$kindKey]`; unknown kinds throw an `InvalidArgumentException` |

Modifiers:

- `->required(false)` — the slot may be missing.
- `->default($value)` — fallback used when the key is missing.
- `Slot::if()` rejects both modifiers with a `LogicException`.
- `Slot::child($name, $shape, $map)` / `Slot::each($name, $shape, $map)` /
  `Slot::eachKind(..., $map)` — derive the nested scope with a closure instead
  of reading `$data[$name]`; this is how a component maps its own props to a
  child component (for example `fn (array $d) => ['href' => '#' . $d['icon']]`).

Values must be stringable: `null`, scalars, and `Stringable` are accepted;
arrays and other objects raise an `InvalidArgumentException` naming the full
slot path.

## Static Subtree Folding

A subtree that contains no slots is static markup. The compiler folds it into a
single literal by rendering it once at compile time, so such subtrees cost
nothing at render time:

```php
$shape = Compile::shape(div(Header(), Slot::text('title')));
```

`Header()` is emitted as a literal; only `title` remains dynamic.

## Structure Fingerprint

`Shape::id()` is a sha1 fingerprint of the shape's structure: tag names,
attributes, slot kinds and names, defaults, nested shapes, map closures (file
and line) and a library cache version. It is computed without compiling and is
used as the cache file name and as a component cache key:

```php
$shapes[$classList . '|' . $item->id()] ??= Compile::shape(...);
```

## Renderer API

```php
$compiled = $shape->compile();

$compiled->render($data);         // string
$compiled->save($path, $data);    // write to file, returns bytes written
$compiled->source;                // generated PHP source (empty for precompiled artifacts)
$compiled->header;                // document header captured at compile time
$compiled->id;                    // structure fingerprint (same as Shape::id())
```

`Shape::save($path, $data)` is the user-facing shortcut: it writes the rendered
output, prepending the document header of the root tag (for example
`<!DOCTYPE html>` or the XML declaration) unless you pass your own header.
`Renderer::save()` uses the header captured at compile time when `$header` is
null — empty for renderers compiled at runtime, the root tag's header for
[precompiled artifacts](/guide/compiled#precompiled-artifacts). `Renderer::$header`
exposes that header, so a handler can print a whole document with
`$renderer->header . $renderer->render($data)`.

## On-Disk Cache

Disabled by default. Enable it once during bootstrap:

```php
use Pure\Compile\Compile;

Compile::cachePath(__DIR__ . '/var/cache/purephp');
```

- Cache files are named by `Shape::id()`, written atomically (temp file +
  rename) and contain plain PHP returning the compiled closure, so opcache can
  serve them.
- A cache entry whose header does not match the expected id, map count, cache
  version or PHP version is discarded and regenerated.
- Renderers that reference component maps stay valid because the map closures
  live in the shape; only the generated code is cached.
- `Compile::clearCache()` deletes the files written by the library.
- Generated sources are memoized per fingerprint in memory as well, so building
  the same tree again in one process re-evaluates the cached source instead of
  regenerating it; map closures stay live, so a rebuilt shape binds its own
  closures. The memo is bounded by a byte budget (the oldest sources are dropped
  first and a source larger than the budget is not kept), so a structure that
  varies per request cannot grow it without limit. Set the environment variable
  `PURE_COMPILE_MEMO_BYTES` to change the budget (`0` disables the memo).
- `Compile::flush()` invalidates in-memory renderers (every shape recompiles on
  next use); it does not delete cache files.

The directory must be private: owned by the PHP user and not writable by group
or others (`cachePath()` creates missing directories with 0700 and rejects
loose or foreign-owned ones), and it should live outside the web root. Do not
point it at a shared location such as `/tmp` itself. Delete the cache between
deploys only if you want to force regeneration; edits to map closures do not
change the fingerprint, so either clear the cache or bump
`Compile::CACHE_VERSION`.

## Per-Request Guard

Compiling a shape per request is slower than rendering a compiled one. Enable
the development guard to detect it:

```php
Compile::guard(true); // or PURE_COMPILE_GUARD=1
```

When the same call site calls `Compile::shape()` more than 20 times in one
process, an `E_USER_WARNING` suggests the `static $shape ??=` pattern.

## Errors

- Missing required slot: `Pure\Core\MissingSlotException` with the full path,
  for example `slot 'items[].title' is required but was not provided.`
- Wrong placement (`Slot::attr` as a child, `Slot::text` as an attribute value)
  or a missing shape: `LogicException` at compile time.
- `Slot::eachKind()` with no variants or with an empty/numeric kind key:
  `InvalidArgumentException` at build time (and `required()`/`default()` on
  `Slot::if()` throw a `LogicException`).
- Non-iterable list, non-array item or scope, an item whose discriminator is
  missing or unknown, non-stringable value: `InvalidArgumentException` at render
  time.

## Trees with Slots Cannot Use Other Output Paths

`render()`, `print()` and `save()` throw a `LogicException` for trees that
contain slots, because there is no data to bind. `toJSON()` describes slots as
`['slot' => 'name']`.

## Performance

Measured on PHP 8.4 (604-element page, 200 rows; reproduce with
`php bench/compare.php`):

| Path | Time per render |
| --- | --- |
| build tree + `render()` | ~700–750 µs |
| render only (same tree reused) | ~220–230 µs |
| compiled shape + data | ~120 µs |
| compiled static tree (literal) | < 1 µs |

The bootstrap features example renders about 10× faster with the compiled path.

Benchmarks are manual, not part of CI:

```bash
php bench/compare.php
php examples/bootstrap-features/bench.php
```

## Limitations

- Tag names cannot depend on data: a shape always uses the same tags. Use
  `Slot::if()` / `Slot::eachKind()` for structural variation, or normalize the
  data before rendering.
- Shapes only persist for the lifetime of a PHP process. In long-running
  workers (or with `opcache.preload`) that is once per worker; under standard
  PHP-FPM the shape tree is rebuilt and the renderer regenerated on every
  request, which is slower than `render()`. Enable `cachePath()` so requests
  load the generated renderer instead of regenerating it.
- Compiled renderers trade compilation for speed: compiling a shape that is
  rendered once per process is slower than `render()`. Compile pages and
  components that are rendered repeatedly.
- Map closures are fingerprinted by file and line; editing a closure body in
  place does not invalidate the cache.
