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
$item = Compile::shape(li(Slot::value('title')));

$root = Compile::shape(
    div(
        h1(Slot::value('heading')),
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
| `Renderer` | The compiled renderer; `render($data)`, `save($path, $data)`, and the readonly `source` / `id` / `slots` properties |
| `Slot` | A placeholder for data, bound at render time |

## Slot Types

| Constructor | Value | Behavior |
| --- | --- | --- |
| `Slot::value($name)` | scalar / `Stringable`; `null` only in an optional or attribute slot | coerced to string and escaped in child position (`true`→"1", a required slot rejects `null`); in attribute position follows `setAttr()` (`true`→`name="name"`, `false`/`null` omitted) |
| `Slot::raw($name)` | stringable, or an iterable of those | emitted verbatim, never escaped; an iterable is stringified element by element and concatenated |
| `Slot::child($name, $shape)` | array | nested data scope for `$shape` |
| `Slot::each($name, $shape)` | iterable of arrays | renders `$shape` for every item |
| `Slot::if($name, $then, $else = null)` | truthy check | renders `$then` when `$data[$name]` is truthy, otherwise `$else`; a missing key is false and never throws |

Modifiers:

- `->required(false)` — the slot may be missing; a missing key and an explicit
  `null` both render empty (an attribute is omitted instead).
- `->default($value)` — fallback used when the key is missing; also makes the
  slot optional.
- A required value or raw slot accepts neither a missing key nor an explicit
  `null`.
- `Slot::if()` rejects both modifiers with a `LogicException`.

Value coercion: scalars and `Stringable` are accepted for value/raw slots, so a
component's string result needs no `(string)` cast; an optional slot also accepts
`null`. A raw slot also accepts an iterable of stringable values, which it
concatenates — a list of rendered rows can go in as it is, without `implode()`. A
nested array still raises an `InvalidArgumentException` naming the full slot
path.

> **Trust boundary** — after rendering returns a `string`, there is no type
> distinction between "trusted rendered markup" passed into a raw slot and
> ordinary text passed into a value slot. Trust is now carried by the raw slot
> contract itself: values in raw slots are emitted verbatim, values in value
> slots are always escaped.

## Scope and Missing Data

`Slot::child()` and `Slot::each()` create a nested data scope; inside it, slots
resolve against that scope. Missing required keys throw
`Pure\Core\MissingSlotException` with the full path, for example
`slot 'items[].title' is required but was not provided.`; the message suggests
the closest provided key (a misspelled binding) or lists the keys the scope did
provide, and an explicit `null` fails a required value or raw slot with
`slot 'items[].title' is required but was null.` Use `default()` or
`required(false)` for optional data.

`Slot::if()` branches share the current scope, so this works naturally:

```php
$item = Compile::shape(
    li(
        Slot::value('name'),
        Slot::if('admin', span('(admin)'))
    )
);
```

## Components

A component is a `*.cmp.php` unit: a call function returning a
`Pure\Component\Call`, plus the lazy factory registered next to it and the
`prepare()` hook that types its props (see [Components](/guide/components) and
[Caching](#caching) for the PHP-FPM case):

```php
<?php

// Card.cmp.php
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{div, h2, p};

function Card(mixed ...$children): Call
{
    return component(__FUNCTION__, ...$children);
}

register(Card(...),
    factory: static fn () =>
        div(
            h2(Slot::value('title')),
            p(Slot::value('content'))
        )->class(Slot::value('class')),
    prepare: static function (string $title, string $content, string $class = 'card'): array {
        return ['title' => $title, 'content' => $content, 'class' => $class];
    }
);

echo Card()->title('Title')->content('Content');
```

Inside a template, nested shapes use `Slot::child()`, lists use `Slot::each()`,
optional/conditional markup uses `Slot::if()`, and rendered child components
enter through `Slot::raw()`. Mixed-list dispatch happens in the data layer, see
the [Mixed Lists](#mixed-lists) appendix.

### Lists

```php
$row = Compile::shape(li(Slot::value('label')));

$shape = Compile::shape(ul(Slot::each('rows', $row)));
$shape(['rows' => [['label' => 'a'], ['label' => 'b']]]);
```

## Caching

Production needs are simple: **use `pure compile` for artifacts (production),
and optionally set `Compile::cachePath($dir)` for long-running workers**. The
cache is content-addressed by shape fingerprint; writes are atomic; directories
must be private (0700). `clearCache()` removes own files, `flush()` drops in-memory
renderers. See [Cache & Ops Details](#cache-ops-details) for the full API surface,
`guard()` warnings, and opcache considerations.

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
  `MissingSlotException` (an explicit `null` fails a required value or raw slot,
  an attribute slot keeps omitting itself), `default:` supplies the compiled
  default of an optional slot, and values are escaped or coerced exactly like the
  flat renderer does. `path:` only appears where the slot path differs from the key.
- An artifact also carries the root slot manifest (`Renderer::$slots`), so the
  development guard can report bindings the template never reads without
  rebuilding the shape tree.

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
XML or SVG one) is the caller's to prepend — `renderHTML()` / `renderXML()` do it
for a tag tree or a component call, and the root tag's `documentHeader()` gives
the header when a `Renderer` is rendered directly. Components in
`examples/bootstrap` are units built on that:

```php
// components/Icon.cmp.php: typed props, backed by its precompiled template

function Icon(mixed ...$children): Call
{
    return component(__FUNCTION__, ...$children);
}

register(Icon(...),
    factory: static fn () =>
        svg(svgUse()->href(Slot::value('href')))->class(Slot::value('class')),
    prepare: static function (string $href, string $class = 'bi'): array {
        return ['href' => $href, 'class' => $class];
    }
);

// views/features.cmp.php: the page skeleton, its blocks supplied by prepare()
function Features(mixed ...$children): Call
{
    return component(__FUNCTION__, ...$children);
}

register(Features(...),
    factory: static fn () => html(/* ... */),
    prepare: #[Binds('title', 'content')] static fn (): array => featuresBindings()
);

function featuresPage(): string
{
    // renderHTML() prepends the document header; the tree renders without one.
    return renderHTML(component('Features'));
}
```

Each block fetches its own records from the service layer (`FeaturesService` in
the bootstrap example), so the page function carries no page data and adding a
prop to a component never touches the page.

A child component's markup goes straight into a raw slot — no `(string)` cast —
and a list of them is concatenated in order.

`Registry::component()` returns the binder of a unit or shape file and loads its
artifact when one exists next to it and is at least as new as the file;
otherwise it calls the registered factory (once per compile generation) or
compiles the shape file (the disk cache still applies). A call returns the
fragment only — the document header, if you want one, is the caller's to
prepend.

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

### Contract Check

`pure check` validates the contract of every unit statically, so a mismatch
fails in CI instead of at render time:

```bash
vendor/bin/pure check src
```

- The **slots** a template reads against the **bindings** its unit returns: a
  binding the template does not read is an error (with a `did you mean`
  suggestion), and a required slot the hook does not bind is an error. A
  returned bindings array is resolved when it is a single array literal — or a
  `...bindings()` helper result declared with `#[Binds]` — and reported as
  `info` when it is computed at runtime.
- The **parameter types** of the `prepare()` hook against the slot kinds: a
  list slot needs an iterable, a child scope an array, a text slot a stringable,
  a raw slot either. A nullable parameter for a required slot is a warning
  (binding `null` throws `MissingSlotException`), as is a parameter that is
  neither used in the hook nor a slot of the template.
- A unit without `prepare()` passes its props straight to the bindings, so it is
  checked at its **call sites** instead: the setters must match the template's
  slots, and a function next to the unit that does not return
  `Pure\Component\Call` is an error.
- **Declarations** state what a signature cannot. `#[Prop]` carries `slot`
  (the binding the prop fills), `item` (the single slot each item of a list prop
  fills in a `Slot::each` item shape), `required` (the caller obligation) and
  `deprecated` (a migration hint); `#[Trusted]` marks a prop that carries
  markup, so it must bind a raw slot and the development guard warns when a call
  passes a value that is not `Pure\Core\Markup`; `#[Binds]` on a hook or a
  `...bindings()` helper declares the returned keys when its array literal
  cannot be read. Declarations are compared with the signature and the template,
  and when `prepare()` does not return one readable array literal they are what
  the required slots are checked against. A call site binding a deprecated prop
  is a warning.
- The **fluent calls** in every checked file: a `->prop(...)` the target does
  not accept is an error with a `did you mean`, `->children(...)` points at the
  call syntax instead, and a prop set unpacked from a variable is skipped. A
  list prop bound to an array literal is compared item by item with the item
  shape of its slot (a missing required key and an unread key are both errors).
  The target must be among the checked files for its props to be known.
- A slot name one template uses as both a scalar (value/raw) and a scope
  (child/each) is an error; `*.shape.php` templates are checked for that too.

Exit code 1 on errors, and on warnings with `--strict`. `pure check` does not
look at artifacts — `pure compile --check` is the freshness check.

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
  `Compile::cachePath()`; the per-path binder cache (built into
  `Registry::component()`, `static $render` for inline trees) keeps the
  renderer in memory, so artifacts are optional.
- **`opcache.preload`** — preloading keeps code in memory but does not carry
  static variables across requests (PHP's preload RFC states this explicitly),
  so it is not a substitute for either of the above.

With opcache, requiring the artifacts of every component on a page costs about
half a microsecond each (22 artifacts load in ~10 µs; see
`bench/registry.php`), so artifacts plus opcache are the production path. A
single-file bundle was prototyped and rejected on that data: it compiled slower
cold than the readable templates together and tied warm, so the library ships
no bundle.

### Dependency-Free Exports (Optional)

`pure compile --plain` writes a `*.plain.php` view: markup and native PHP that
runs without purephp installed. Load it by extracting data into locals:

```php
ob_start();
extract($data, EXTR_SKIP);
require 'views/index.plain.php';
$html = (string)ob_get_clean();
```

Reach for `--plain` when views must run without the library — a deployment that
ships only `public/` and `views/`, or a template directory handed to someone else.
For ordinary data a plain view renders the artifact's bytes exactly, preceded by
the document header only when its root is a document root (`<html>` or an XML
tree): a page keeps its `<!DOCTYPE html>` / XML declaration, while a fragment
(a `div`, an inline SVG icon) starts with its markup, so including it never
injects a header into the middle of a document. It is also the fastest form:
values go straight into `htmlspecialchars()`, with no runtime accessor calls.
It is a plain view, not a compiled component, so the strict slot semantics stay
with the artifact. See [Plain View Caveats](#plain-view-caveats) for the
semantic differences (undefined variable on missing slot, empty string on null
attribute, no iterable check on lists, PHP coercion of raw elements).

## Performance

Two costs matter per request: what a process pays to get a renderer, and what it
pays to render with it. `bench/README.md` holds the recorded rows; absolute
numbers move with the PHP version, opcache and the CPU, so run the scripts
before comparing them with the table below (PHP 8.1.34, one 604-element page of
200 rows, `php bench/compare.php`).

| Path | Time per render | End-to-end speedup |
| --- | --- | --- |
| build tree + `Tag::render()` | ~1.2 ms | 1× |
| render only (same tree reused) | ~345 µs | 3.4× |
| compiled shape + data | ~180 µs | 6.6× |
| compiled static tree (literal) | < 1 µs | — |

With opcache the build stays expensive while the compiled path barely changes, so
the speedup lands at 5.5×, and 4.6× with the JIT on. The precompiled artifact
path removes the build from that second column entirely: for one page shape,
building and compiling cost ~2.9 ms against ~25–67 µs to require its artifact
(`php bench/artifact.php --write && php bench/artifact.php`).

What a whole page costs depends on how it is composed. The bootstrap features
page builds its body from component calls, so `examples/bootstrap/bench.php`
measures the real page, not one shape: ~340 µs/op for the classic tree, ~104 µs/op
for the page function over its artifact (2.8–3.3×), and ~20 µs/op for the plain
view. The benchmark's `skeleton artifact + bindings` row renders the page
*template* with the component markup already bound, so its ~1.5 µs is a per-shape
figure, not a page render.

## Limitations

- Tag names cannot depend on data: a shape always uses the same tags. Use
  `Slot::if()` for conditional markup or dispatch mixed lists in the data layer
  ([appendix](#mixed-lists)), or normalize the data before rendering.
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
| `function Card(array $props): HTML` | `function Card(string $title): Call` with a `Card.cmp.php` unit (call function + template) and a `prepare()` hook |
| `h2($title)` | `h2(Slot::value('title'))` |
| `->class($classList)` | `->class($classList)` for static values, `->class(Slot::value('classList'))` for dynamic ones |
| `array_map(fn ($row) => Row($row), $rows)` | loop in `prepare()` (or at the call site) and bind the joined markup to a raw slot |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | call `Child(...)` and inject the returned markup through `Slot::raw()` |

Immediate tag-tree rendering (`Tag::render()`) remains available for snippets
and debugging; see [Basic Usage](/guide/basic-usage).

## Cache & Ops Details

- `Compile::cachePath($dir)` enables the on-disk renderer cache; pass `null`
  to disable (default). The directory must be private: owned by the PHP user,
  not writable by group or others (`0700` is created when missing), and outside
  the web root — `cachePath()` rejects loose or foreign-owned directories.
- `Compile::clearCache()` deletes the files written by the library.
- `Compile::flush()` invalidates in-memory renderers (useful in long-running
  workers after a deploy).
- To catch shapes that are rebuilt per request, enable the development guard:
  `Compile::guard(true)` or set `PURE_COMPILE_GUARD=1`. When the same call site
  calls `Compile::shape()` too many times, PHP emits an `E_USER_WARNING`
  suggesting the `static $shape ??=` pattern. The same switch turns on the
  render-time checks: data keys the rendered template never reads are reported
  (with a `did you mean` suggestion) and a setter whose attribute name is one
  edit away from a standard one warns instead of silently creating a custom
  attribute. Each warning fires once per subject per process.
- `Compile::CACHE_VERSION` is bumped when the generated-code format or
  fingerprint composition changes. An artifact written by another version
  throws `RuntimeException` on load with a message naming `pure compile`.
  `*.plain.php` views carry the version in a comment but have no executable
  guard, so they silently serve stale output until `pure compile --check --plain`
  catches the mismatch.

## Plain View Caveats

A plain view is markup and native PHP — it renders without purephp installed,
but it does not carry the strict slot semantics of the compiled renderer:

- a missing required slot is an undefined variable, not `MissingSlotException`;
- a required slot bound to `null` renders empty instead of failing (the artifact
  rejects it for value and raw slots);
- the plain view of a document root starts with its `<!DOCTYPE html>` / XML
  declaration, while a fragment view starts with its markup;
- a `null` attribute prints an empty value instead of disappearing;
- list slots are not checked for being iterable, and values are stringified by
  PHP rather than by `SlotRuntime`;
- a raw slot joins an iterable of stringable values with `implode('')`, like the
  artifact; an element PHP cannot stringify is coerced with a warning (`Array`)
  instead of raising the artifact's `InvalidArgumentException`.

A plain view is an include: enable opcache in production, or every render parses
the file again.

The view declares every root slot with an `@var` annotation derived from the
shape, so static analyzers read the extracted locals without an exclusion:

```php
/**
 * @var scalar|null|\Stringable $title
 * @var array{columns: ...} $content
 */
```

Value slots are `scalar|null|\Stringable`, condition slots are `mixed`, and
child/list scopes become array shapes and iterables of them. Odd slot names are
declared on the loader's `$data` array. Annotations are comments: they add no
output bytes.

## Mixed Lists

A shape has one structure, so a list whose items need different markup is
dispatched in the data layer: render each item through the call function that
fits it and pass the joined markup into a raw slot.

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

$blocks = [
    ['kind' => 'link', 'value' => 'Docs', 'href' => '/docs'],
    ['kind' => 'text', 'value' => 'Hello'],
];

$shape = Compile::shape(div(Slot::raw('blocks')));
$shape(['blocks' => Blocks($blocks)]);
```

`Slot::each()` covers the homogeneous case: one shape, every item. When the
variants are only conditional details inside one item shape, `Slot::if()` on
precomputed keys keeps the dispatch in the template.
