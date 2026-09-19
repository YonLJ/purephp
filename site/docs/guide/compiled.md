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

$root = Compile::shape(
    div(
        h1(Slot::text('heading')),
        ul(Slot::each('items', $item))
    )->class('card')
);

// Rendering binds plain data.
echo $root([
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
| `Slot::raw($name)` | stringable, `null`, or an iterable of those | emitted verbatim, never escaped; an iterable is stringified element by element and concatenated |
| `Slot::child($name, $shape)` | array | nested data scope for `$shape` |
| `Slot::each($name, $shape)` | iterable of arrays | renders `$shape` for every item |
| `Slot::if($name, $then, $else = null)` | truthy check | renders `$then` when `$data[$name]` is truthy, otherwise `$else`; a missing key is false and never throws |
| `Slot::eachKind($name, ['kind' => $shape, ...])` | iterable of arrays | dispatches every item on `$item['kind']`; an unknown kind throws an `InvalidArgumentException` |

Modifiers:

- `->required(false)` — the slot may be missing.
- `->default($value)` — fallback used when the key is missing.
- `Slot::if()` rejects both modifiers with a `LogicException`.

Value coercion: `null`, scalars and `Stringable` are accepted for
text/attribute/raw slots, so the `Raw` a child component returns needs no
`(string)` cast; arrays and other objects raise an `InvalidArgumentException`
naming the full slot path. Only a raw slot additionally accepts an iterable of
stringable values, which it concatenates — a list of rendered rows can go in as
it is, without `implode()`. A nested array is still an error.

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
        Slot::if('admin', span('(admin)'))
    )
);
```

## Components

A component is a `*.cmp.php` unit: a function with typed parameters returning
`Raw`, plus the lazy factory registered next to it (see
[Components](/guide/components) and [Caching](#caching) for the PHP-FPM case):

```php
<?php

// Card.cmp.php
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
    )->class(Slot::attr('class'))
));

function Card(string $title, string $content, string $class = 'card'): Raw
{
    return render('Card', title: $title, content: $content, class: $class);
}
```

Inside a template, nested shapes use `Slot::child()`, lists use `Slot::each()`,
mixed lists use `Slot::eachKind()`, optional/conditional markup uses
`Slot::if()`, and rendered child components enter through `Slot::raw()`.

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

- `pure compile <path>...` accepts files and directories (searched recursively),
  discovers both `*.shape.php` templates and `*.cmp.php` units, and skips the
  files whose content is already current: the shape is still loaded and compiled
  (so a change in anything it pulls in is picked up), but an up-to-date file is
  reported as `unchanged:` instead of rewritten. `--list` prints every unit as
  `name -> file (component|page)` without compiling. `pure compile --check`
  writes nothing and exits with code 1 when an artifact is stale or missing,
  which fits a CI step. `--plain` also writes the dependency-free view described
  below, and `--check --plain` covers both flavors. The repository examples ship
  `*.cmp.php` units, so `vendor/bin/pure compile examples` compiles them all.
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
$pureBody = static function (array $v): string {
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

There is no automatic document header: the tree renders as written, and a full
document's header (`<!DOCTYPE html>` for an HTML root, the XML declaration for an
XML or SVG one) is the caller's to prepend, via the root tag's `documentHeader()`.
Components in `examples/bootstrap` are units built on that:

```php
// components/Icon.cmp.php: typed props, backed by its precompiled template
register('Icon', __FILE__, static fn (): Shape => Compile::shape(
    svg(svgUse()->href(Slot::attr('href')))->class(Slot::attr('class'))
));

function Icon(string $href, string $class = 'bi'): Raw
{
    return render('Icon', href: $href, class: $class);
}

// views/features.cmp.php: the page skeleton plus the rendered body
register('Features', __FILE__, static fn (): Shape => Compile::shape(/* ... */));

function featuresPage(array $data): Raw
{
    // Prepend the document header; the tree itself renders without one.
    return Raw::of('<!DOCTYPE html>' . (string)render('Features',
        title: $data['title'],
        content: FeaturesBody($data['content']),
    ));
}
```

A child component's `Raw` goes straight into a raw slot — no `(string)` cast —
and an array of them is concatenated in order.

`Pure\Component\render()` loads the artifact of a unit or shape file when one
exists next to it and is at least as new as the file; otherwise it calls the
registered factory (once per compile generation) or compiles the shape file (the
disk cache still applies). It returns the fragment only — the document header,
if you want one, is the caller's to prepend. The binder underneath is `bind()`,
which you can hold yourself for inline trees.

Its `PlainFeaturesController` passes the same bindings through the example's
`plain()` helper (an app function: it requires the view file and extracts the
data), and one router (`public/index.php`) serves every page in both flavors —
`/pure/features` and `/pure/pricing` render the page functions while
`/plain/features` and `/plain/pricing` render the plain views — so you can
compare the flavors while developing.
- Build artifacts with the same PHP minor version as production: the fingerprint
  and the artifact header embed the PHP version, as the cache does.
- Artifacts are build output: rebuild them after changing a shape. Loading does
  not verify the shape tree, so `--check` is the way to notice a stale artifact.
- An artifact also carries its `Compile::CACHE_VERSION`: loading one written by
  another version of the library throws with a `pure compile` message instead of
  failing on the `Renderer` signature it describes. A version bump invalidates
  the `Compile::cachePath()` renderers on its own, never the artifacts beside
  your templates, so `pure compile` is part of an upgrade.
- Freshness is compared with `filemtime()`, whose whole-second granularity means
  an artifact written in the same second as its unit already serves it. This is
  deliberate: `touch`-style skew from a tar, rsync or git checkout is common,
  and an exact comparison would discard those artifacts and recompile them per
  request. A content hash of every unit measured ~7 µs per file against ~0.5 µs
  to require its artifact with opcache, so it is not a cheaper guard either.
- Two source files that would write the same artifact (`a.shape.php` beside
  `a.cmp.php`) are both rejected by `pure compile` with exit code 1, so
  discovery order cannot decide which template owns `a.pure.php`.
- Output echoed while a shape file loads is discarded; build messages are the
  only thing `pure compile` writes.

### Component Artifacts and Caching

Every component is a `*.cmp.php` unit (a `*.shape.php` template also works), so
`pure compile` builds it like any other shape. The binder checks the artifact
mtime against the unit file: a fresh artifact is loaded as-is (no factory call,
no shape tree, no fingerprint), a stale or missing one calls the registered
factory or compiles the shape file. In CI, `pure compile --check` reports stale
artifacts with exit code 1.

What to enable depends on the deployment:

- **PHP-FPM** — enable `Compile::cachePath()` and build artifacts. Without an
  artifact every request rebuilds the component's shape tree and walks its
  fingerprint before rendering: the features page skeleton alone compiles in
  ~780 µs cold and ~260 µs from the disk cache (`bench/cache.php`). An artifact
  cuts that to one `require`, and with opcache a require is well under a
  microsecond.
- **Long-running workers** (RoadRunner, Swoole, FrankenPHP) — enable
  `Compile::cachePath()`; the per-path binder cache (built into `render()`,
  `static $render` for inline trees) keeps the renderer in memory, so
  artifacts are optional.
- **`opcache.preload`** — preloading keeps code in memory but does not carry
  static variables across requests (PHP's preload RFC states this explicitly),
  so it is not a substitute for either of the above.

With opcache, requiring the artifacts of every component on a page costs about
half a microsecond each (22 artifacts load in ~10 µs; see
`bench/registry.php`), so artifacts plus opcache are the production path. A
single-file bundle was prototyped and rejected on that data: it compiled slower
cold than the readable templates together and tied warm, so the library ships
no bundle.

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
  PHP rather than by `SlotRuntime`;
- a raw slot prints exactly one value: a `Raw` or any `Stringable` is fine, an
  iterable of them is not, because the view has no `SlotRuntime::raw()` to join
  it. Pass `implode('', $rows)` from the controller, or bind a string.

With function components the controller renders the components first and passes
their markup as the raw bindings the page shape prints, so the view file stays
dependency-free while the request handler uses the library.

The view declares every root slot with an `@var` annotation derived from the
shape, so static analyzers read the extracted locals without an exclusion and
without configuration:

```php
/**
 * @var scalar|null|\Stringable $title
 * @var array{columns: array{title: scalar|null|\Stringable, contents: iterable<array-key, array{title: scalar|null|\Stringable}>}} $content
 */
```

Value slots are `scalar|null|\Stringable` (what `htmlspecialchars()` accepts),
condition slots are `mixed`, and child and list scopes become array shapes and
iterables of them. Odd slot names are declared on the loader's `$data` array.
The annotations are comments: they add no output bytes. An optional container
slot without an array default stays flagged, because its generated read falls
back to `null` and the annotation says so.

Reach for `--plain` when the views have to run without the library — a
deployment that ships only `public/` and `views/`, or a template directory
handed to someone else. Views are includes, so enable opcache: without it every
render parses the file again, which is the one case where the artifact wins.

## Performance

Two costs matter per request: what a process pays to get a renderer, and what it
pays to render with it. `bench/README.md` holds the recorded rows; absolute
numbers move with the PHP version, opcache and the CPU, so run the scripts
before comparing them with the table below (PHP 8.1.34, one 604-element page of
200 rows, `php bench/compare.php`).

| Path | Time per render | End-to-end speedup |
| --- | --- | --- |
| build tree + `render()` | ~1.2 ms | 1× |
| render only (same tree reused) | ~345 µs | 3.4× |
| compiled shape + data | ~180 µs | 6.6× |
| compiled static tree (literal) | < 1 µs | — |

With opcache the build stays expensive while the compiled path barely changes, so
the speedup lands at 5.5×, and 4.6× with the JIT on. The precompiled artifact
path removes the build from that second column entirely: for one page shape,
building and compiling cost ~2.9 ms against ~25–67 µs to require its artifact
(`php bench/artifact.php --write && php bench/artifact.php`).

What a whole page costs depends on how it is composed. The bootstrap features
page builds its body from component functions, so `examples/bootstrap/bench.php`
measures the real page, not one shape: ~340 µs/op for the classic tree, ~104 µs/op
for the page function over its artifact (2.8–3.3×), and ~20 µs/op for the plain
view. The benchmark's `skeleton artifact + bindings` row renders the page
*template* with the component markup already bound, so its ~1.5 µs is a per-shape
figure, not a page render.

## Limitations

- Tag names cannot depend on data: a shape always uses the same tags. Use
  `Slot::if()` / `Slot::eachKind()` for structural variation, or normalize the
  data before rendering.
- Compiled code is tied to the shape structure; changing a shape changes its
  `id()` and therefore its cache file.
- A shape tree is read live while it compiles, and `id()` reflects the tree as
  it is at that moment. An already compiled renderer keeps rendering the tree
  state it was built from, so call `Compile::flush()` after mutating a tree that
  is already wrapped in a shape; building shapes once per process avoids this
  entirely.
- Shapes must not contain request data — they are process-level artifacts.

## Classic Component → PurePHP Mapping

| Classic component | PurePHP component |
| --- | --- |
| `function Card(array $props): HTML` | `function Card(string $title): Raw` with a `Card.cmp.php` unit (function + template) |
| `h2($title)` | `h2(Slot::text('title'))` |
| `->class($classList)` | `->class($classList)` for static values, `->class(Slot::attr('classList'))` for dynamic ones |
| `array_map(fn ($row) => Row($row), $rows)` | loop in the component function and inject through `Slot::raw()` |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | call `Child(...)` and inject its `Raw` through `Slot::raw()` |

Immediate (`render()`) tag trees remain available for snippets and debugging;
see [Basic Usage](/guide/basic-usage).
