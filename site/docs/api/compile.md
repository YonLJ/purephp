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
$item  = Compile::shape(li(Slot::value('title')));
$shape = Compile::shape(
    div(
        h1(Slot::value('heading')),
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
| `Pure\Compile\Renderer` | The compiled renderer: `render($data)`, `save($path, $data, $header = '')` and the readonly `source` / `id` properties |
| `Pure\Core\Slot` | Placeholder constructors (`value`, `raw`, `child`, `each`, `if`, `eachKind`) and modifiers |
| `Pure\Core\MissingSlotException` | Thrown when a required slot is missing, with the full path |

## Function Components

A component unit registers a lazy template factory under a name; the component
function next to it renders that name. `Pure\Component\render()` returns the
rendered markup as a string in one expression:

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn () =>
    div(h2(Slot::value('title')), p(Slot::value('content')))->class('card')
);

function Card(string $title, string $content): string
{
    return render('Card', title: $title, content: $content);
}
```

| Function | Behavior |
| --- | --- |
| `register(string $name, string $file, Closure $factory, bool $override = false): void` | Registers a component unit; the factory must be lazy and may return a tag tree or a `Shape` |
| `render(string $source, mixed ...$data): string` | Renders a unit by name or a template by path; the binder is cached |

There is no page flavour: to emit a full document, prepend the header of the
root tag yourself (`$root->documentHeader()`, or a literal `<!DOCTYPE html>` /
`<?xml version="1.0"?>`).

`render()` takes slot values as named arguments (`render('Card', title: $title)`)
or as an unpacked array with string keys; positional data is rejected with a
`RuntimeException`. To hold or pass around the binder yourself, use
`Registry::component($source)`, which returns a `Closure(array $data): string`.

The source is a registered name, the path of a `*.cmp.php` unit or the path of
a `*.shape.php` template. Registering the same name for another file, or another
name for the same file, throws unless `override: true` is passed; one unit file
registers one component. A name and the path of its unit file resolve to the
same binder.

For a file, the sibling `*.pure.php` artifact is loaded when it exists and is at
least as new as the unit or shape file, so production skips calling the factory
and building the shape tree; otherwise the factory runs (once per compile
generation) or the shape file is compiled (the disk cache still applies).
Missing files, a template that does not return a tag tree or a `Shape` and an
artifact that does not return a `Renderer` all raise a `RuntimeException` naming
the file. Run `pure compile` to build artifacts for every `*.shape.php` and
`*.cmp.php` file, `pure compile --list` to print the units found, and
`pure compile --check` to keep artifacts fresh in CI.

## Shape vs. Data

A shape is a normal tag tree in which dynamic values are replaced by `Slot`
placeholders. Shapes must not contain request data, and must be built **once
per process** — file-backed templates get that from the per-path binder cache
in `render()`, inline trees from a `static` variable inside the component
function, never inside a request handler.

| Classic component | PurePHP component |
| --- | --- |
| `function Card(array $props): HTML` | `function Card(string $title): string` with a `Card.cmp.php` unit (function + template) |
| `h2($title)` | `h2(Slot::value('title'))` |
| `->class($classList)` | `->class($classList)` for static values, `->class(Slot::value('classList'))` for dynamic ones |
| `array_map(fn ($row) => Row($row), $rows)` | loop in the component function and inject the joined markup through `Slot::raw()` |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | call `Child(...)` and inject the returned markup through `Slot::raw()` |

A child component's markup is a plain string, so it enters a template through a
raw slot — a bare string child would be escaped as text:

```php
$shape = Compile::shape(div(Slot::raw('header'), Slot::each('rows', $row))->class('page'));
$shape(['header' => Header(), 'rows' => $rows]);
```

## Slot Types

| Constructor | Value | Behavior |
| --- | --- | --- |
| `Slot::value($name)` | stringable or `null` | position decides the semantics: child position coerces to string and escapes (`null` renders as empty content, `true` as "1"); attribute position follows `setAttr()` (`true` renders `name="name"`, `false`/`null` omit the attribute) |
| `Slot::raw($name)` | stringable, `null`, or an iterable of those | emitted verbatim, never escaped; an iterable is concatenated |
| `Slot::child($name, $shape)` | array | nested data scope for `$shape` |
| `Slot::each($name, $shape)` | iterable of arrays | renders `$shape` for every item |
| `Slot::if($name, $then, $else = null)` | truthy check | renders `$then` when `$data[$name]` is truthy, otherwise `$else`; a missing key is false and never throws |
| `Slot::eachKind($name, ['kind' => $shape], $kindKey = 'kind')` | iterable of arrays | dispatches each item on `$item[$kindKey]`; unknown kinds throw an `InvalidArgumentException` |

Modifiers:

- `->required(false)` — the slot may be missing.
- `->default($value)` — fallback used when the key is missing.
- `Slot::if()` rejects both modifiers with a `LogicException`.

Value and raw slots must be stringable: `null`, scalars, and `Stringable`
(including a `Raw`) are accepted; other objects raise an
`InvalidArgumentException` naming the full slot path. A `raw` slot additionally
accepts an iterable of stringable values and concatenates them verbatim; a
non-stringable element names its offset, e.g.
`slot 'items[2]' must be stringable`.

Pick a list slot by whether the markup is already rendered: `raw()` concatenates
markup that already exists (pass the rendered string, a `Raw`, or a list of
them); `each()` is data-driven and renders every item through its own shape.

## Static Subtree Folding

A subtree that contains no slots is static markup. The compiler folds it into a
single literal by rendering it once at compile time, so such subtrees cost
nothing at render time:

```php
$shape = Compile::shape(div(
    Slot::raw('header'),
    div('Static footer')->class('footer'),
    Slot::value('title')
));
```

`div('Static footer')` is folded into a literal; `header` and `title` stay
dynamic, and already-rendered `Header()` markup enters through the raw slot at
render time.

## Structure Fingerprint

`Shape::id()` is a sha1 fingerprint of the shape's structure: tag names,
attribute names and values, slot kinds and names, defaults, nested shapes and a
library cache version. It is computed without compiling, and it keys the
on-disk renderer cache: the generated source is stored under it, so two shapes
that differ anywhere in the structure cannot share a cached renderer. A
`*.pure.php` artifact records it next to its source, which is how
`pure compile --check` recognizes a stale artifact. It is not what
`Pure\Component\render()` resolves a component by — that is the registered name
or the unit file — and it does not change when only the *data* changes. Where it
does help is an application that assembles a different shape per variant: the
fingerprint is a cheap, stable key for the memo it keeps them in:

```php
$shapes[$classList . '|' . $item->id()] ??= Compile::shape(...);
```

## Renderer API

```php
$compiled = $shape->compile();

$compiled->render($data);                    // string
$compiled->save($path, $data);               // write to file, returns bytes written
$compiled->save($path, $data, $header);      // prepend $header to the file
$compiled->source;                           // generated PHP source (empty for precompiled artifacts)
$compiled->id;                               // structure fingerprint (same as Shape::id())
```

`Shape::save($path, $data)` is the user-facing shortcut: it writes the rendered
output, prepending the document header of the root tag (for example
`<!DOCTYPE html>` or the XML declaration) unless you pass your own header.
`Renderer::save()` instead takes the header as an optional third parameter,
empty by default, so a handler that wants a whole document prepends it itself:
`'<!DOCTYPE html>' . $renderer->render($data)`.

## On-Disk Cache

Disabled by default. Enable it once during bootstrap:

```php
use Pure\Compile\Compile;

Compile::cachePath(__DIR__ . '/var/cache/purephp');
```

- Cache files are named by `Shape::id()`, written atomically (temp file +
  rename) and contain plain PHP returning the compiled closure, so opcache can
  serve them.
- A cache entry whose header does not match the expected id, cache version or
  PHP version is discarded and regenerated.
- `Compile::clearCache()` deletes the files written by the library.
- Generated sources are memoized per fingerprint in memory as well, so building
  the same tree again in one process re-evaluates the cached source instead of
  regenerating it. The memo is bounded by a byte budget (the oldest sources are dropped
  first and a source larger than the budget is not kept), so a structure that
  varies per request cannot grow it without limit. Set the environment variable
  `PURE_COMPILE_MEMO_BYTES` to change the budget (`0` disables the memo).
- `Compile::flush()` invalidates in-memory renderers (every shape recompiles on
  next use); it does not delete cache files.

The directory must be private: owned by the PHP user and not writable by group
or others (`cachePath()` creates missing directories with 0700 and rejects
loose or foreign-owned ones), and it should live outside the web root. Do not
point it at a shared location such as `/tmp` itself. Delete the cache between
deploys only if you want to force regeneration.

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
- Wrong placement (raw slot as an attribute value)
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

`bench/compare.php` measures the paths on a 604-element page, and
`examples/bootstrap/bench.php` measures a real page composed from component
functions; `bench/README.md` holds the recorded rows and [the compiled
guide](/guide/compiled#performance) explains which cost dominates when.
Absolute numbers move with the PHP version, opcache and the CPU, so run them
before comparing:

```bash
php bench/compare.php
php examples/bootstrap/bench.php
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
