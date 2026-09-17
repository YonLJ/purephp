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

## Precompiled Artifacts

The cache still rebuilds the shape tree on every request. To deploy without
building shapes at all, compile them ahead of time with the `pure` command:

```bash
vendor/bin/pure compile src/shapes
```

Every `*.shape.php` file that returns a `Shape` is compiled into a sibling
`*.pure.php` artifact. An artifact declares the shape fingerprint and returns a
`Renderer`, so it needs neither the shape tree nor the compile cache:

```php
$page = require __DIR__ . '/page.pure.php';

echo $page->render(['title' => 'Users']);
$page->save(__DIR__ . '/out.html', ['title' => 'Users']);
```

- `pure compile <path>...` accepts files and directories (searched recursively);
  `pure compile --check` writes nothing and exits with code 1 when an artifact is
  stale or missing, which fits a CI step. `--plain` also writes the
  dependency-free view described below, and `--check --plain` covers both
  flavors. The repository examples ship `*.shape.php` files, so
  `vendor/bin/pure compile examples` compiles them all.
- Artifacts render the same output as the runtime compiler (asserted byte for
  byte by the tests) and read as a template: markup stays markup, values become
  `<?= ... ?>`, control flow uses the alternative syntax, and the closure is
  defined once with imported short class names. HTML runs keep the exact
  rendered bytes, so they are never re-indented. `Renderer::$source` is empty
  for artifacts — the file itself is the source.
- Dynamic values read their slot through `TemplateRuntime`, which keeps the
  compiled semantics in one place: a required slot throws
  `MissingSlotException`, `default:` supplies the compiled default of an
  optional slot, and values are escaped or coerced exactly like the flat
  renderer does. `path:` only appears where the slot path differs from the key.

```php
    $pureBody = static function (array $v, array $maps): string {
        ob_start();
        try { ?><div class="card"><h1><?= TemplateRuntime::text($v, 'title') ?></h1><ul><?php
            foreach (TemplateRuntime::items($v, 'items') as $item1):
                $v2 = TemplateRuntime::scope($item1, 'items[]'); ?><li><?= TemplateRuntime::text($v2, 'label', path: 'items[].label') ?></li><?php
            endforeach; ?></ul></div><?php
        } finally {
            $out = (string)ob_get_clean();
        }

        return $out;
    };
```

`Renderer::$header` holds the document header captured at build time (the
`<!DOCTYPE html>` of an `html()` root), so a request handler only has to print
the view. `examples/bootstrap-features` is a small MVC setup built on that:

```php
// app/controllers/IndexController.php: fetch data and fill the compiled view
function indexController(): string
{
    return view('index.pure', [
        'title' => 'Features · Bootstrap v5.2',
        'content' => [/* … */],
    ]);
}

// app/bootstrap.php: index.pure maps to views/index.pure.php
function view(string $name, array $data = []): string
{
    static $views = [];

    $renderer = $views[$name] ??= require __DIR__ . '/../views/' . $name . '.php';

    return $renderer->header . $renderer->render($data);
}
```

Its `PlainIndexController` returns the same `indexData()` through `plain()`
instead, and a single router (`public/index.php`) serves both: `/` redirects to
`/plain`, `/pure` renders the artifact and `/plain` the plain view, so you can
compare the flavors while developing.
- Build artifacts with the same PHP minor version as production: the fingerprint
  and the artifact header embed the PHP version, as the cache does.
- Artifacts are build output: rebuild them after changing a shape. Loading does
  not verify the shape tree, so `--check` is the way to notice a stale artifact.
- Output echoed while a shape file loads is discarded; build messages are the
  only thing `pure compile` writes.

### Dependency-Free Views

`pure compile --plain` also writes a `*.plain.php` view next to the artifact:
markup and native PHP that renders without purephp installed. Loading it is the
classic view contract — the data array is extracted into locals:

```php
ob_start();
extract($data, EXTR_SKIP);
require 'views/index.plain.php';
$html = (string)ob_get_clean();
```

A root slot reads as an ordinary variable and a nested slot as the array it
lives in, and escaping is inlined, so the view is exactly as portable as a
hand-written template:

```php
<title><?= htmlspecialchars((string)$title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) ?></title>
<h2><?= htmlspecialchars((string)$content['columns']['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) ?></h2>
<?php foreach ($content['columns']['contents'] as $item1): ?><h3><?= htmlspecialchars((string)$item1['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) ?></h3><?php endforeach; ?>
```

For ordinary data a plain view renders byte-identical output to its artifact
(the tests assert it), and it is the fastest flavor: values go straight into
`htmlspecialchars()`, with no runtime accessor call. It is a plain view, not a
compiled component, so the strict slot semantics stay with the artifact:

- a missing required slot is an undefined variable, not `MissingSlotException`;
- a `null` attribute prints an empty value instead of disappearing;
- list slots are not checked for being iterable, and values are stringified by
  PHP rather than by `SlotRuntime`.

Reach for `--plain` when the views have to run without the library — a
deployment that ships only `public/` and `views/`, or a template directory
handed to someone else. Views are includes, so enable opcache: without it every
render parses the file again, which is the one case where the artifact wins.

### Maps in Artifacts

Map closures (the third argument of `Slot::child()`, `Slot::each()` and
`Slot::eachKind()`) are copied into the artifact from the file that defines
them, together with that file's namespace and imports:

```php
$item = Compile::shape(li(Slot::text('label')));

return Compile::shape(
    ul(Slot::each('items', $item, static fn (mixed $item): array => ['label' => '#' . $item]))
);
```

The closure must be copyable on its own:

- it must not be bound to an object, so no `$this` and no tear-offs from
  instances;
- it must not capture variables (`use (...)`, or outer variables in an arrow
  function) — pass data through the slot scope instead;
- it must not use `self`, `parent`, `static::`, `__FILE__`, `__DIR__`,
  `__LINE__`, `__CLASS__` or `__TRAIT__`, whose values depend on the file that
  defines them;
- it must not share its line with another closure.

The same copying rules apply to plain views: a namespaced closure puts the
whole view into a `namespace {}` block, so its namespace and imports stay in
effect there too.

Named callables (`Closure::fromCallable('App\mapItem')`, `mapItem(...)`) are
referenced by name; the function must be loaded when the artifact renders.
When a closure cannot be copied, the compiler reports the slot path and the
reason, and the shape can keep using [Caching](#caching) instead.

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
