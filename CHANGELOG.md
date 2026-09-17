# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

First public version. No tag has been cut yet.

### Added

- Compiled rendering: `Pure\Compile\Compile::shape()` compiles a data-free shape
  tree with `Pure\Core\Slot` placeholders into a flat PHP renderer
  (`Shape`, `Renderer`). Static markup is escaped once at compile time and
  subtrees without slots are folded into literals.
- Slot types `Slot::text()`, `Slot::attr()`, `Slot::raw()`, `Slot::sub()`,
  `Slot::each()`, `Slot::if()` and `Slot::eachAny()`, with `required(false)`
  and `default()` modifiers and optional map closures for derived scopes.
- `Pure\Core\MissingSlotException` with full slot paths for missing data, and
  `InvalidArgumentException` for non-stringable values and list contract
  violations.
- Structure fingerprints (`Shape::id()` / `Renderer::$id`) computed without
  compiling, used as cache keys.
- Optional on-disk renderer cache: `Compile::cachePath()`, `Compile::clearCache()`
  and `Compile::flush()`; cache files are content-addressed, written atomically
  and opcache-friendly.
- Development guard for shapes rebuilt per request: `Compile::guard()` /
  `PURE_COMPILE_GUARD=1` emits an `E_USER_WARNING` after repeated calls from
  the same call site.
- `bench/compare.php`, `bench/cache.php` and `bench/README.md` with recorded
  numbers, plus `composer bench`.
- Compiled guide and API documentation (English and Chinese).

### Changed

- `Renderer::__invoke()` is now the named `Renderer::render($data)`, the
  `Renderer::print()` alias was removed, and `Renderer::$source` / `Renderer::$id`
  are public readonly properties instead of `source()` / `id()` getters, so the
  invokable and echo surfaces stay on the user-facing `Shape`;
  `Shape::__invoke()` and `Shape::print()` delegate to `Renderer::render()`.
- `Tag::toJSON()` now returns a nested structure (`tagName`, `attrs`, `children`)
  so an attribute can no longer overwrite the structural keys.
- `Tag::getAttr()` returns `null` for a missing attribute instead of emitting a
  warning and failing on the return type; `Tag::setAttrByCb()` passes `null` to
  the callback for a missing attribute.
- `Tag::toSave()` moved to the base class as
  `toSave(string $path, ?string $header = null)`; `HTML`, `XML` and `SVG`
  provide their default document headers via `defaultHeader()`. Subclasses that
  override `toSave()` must accept the new optional `$header` parameter (or drop
  the override).
- `Slot::if()` now rejects `required()` and `default()` with a
  `LogicException` instead of silently ignoring them.
- `Pure\Core\RawType` moved to its own file so it is autoloadable on its own;
  `Raw` is final and `Raw::toJSON()` uses the enum name.
- `clx()` accepts and drops booleans (`->class('btn', $cond && 'active')` keeps
  working), accepts numbers in scalar and array position, keeps explicit
  non-empty strings including `"0"`, and no longer exports a
  `filterClassList()` helper.
- Exceptions unified: `BadMethodCallException` for attribute arity,
  `InvalidArgumentException` for invalid attribute names, `LogicException` for
  self-closing conflicts; compile-time violations use the new
  `Pure\Compile\CompileException` (extends `LogicException`).
- Internals: `RendererCache` and `ShapeGuard` extracted from `Compile`;
  `SlotRuntime` replaces `Values` and generated conditions use a plain bool
  cast; `ShapeIndex` is a value object; escaping flags/encoding stay shared
  through `Pure\Core\Escaper` and the attribute chunk format lives in
  `Escaper::attribute()`, while slot text/attribute values inline the same
  `htmlspecialchars` call for speed (byte-identity locked by a cross-path
  test). A single-consumer visitor adapter and single-use exception factories
  were dropped to keep the abstraction surface minimal.
  `Compile::CACHE_VERSION` was bumped for the new fingerprints, so cached
  renderers written by earlier versions are discarded and regenerated.
- `SELF_CLOSE_HTML_TAGS` and `SELF_CLOSE_SVG_TAGS` are keyed sets
  (`['br' => true]`) instead of lists, so membership is an `isset()` lookup
  rather than a linear `in_array()` scan; constructing a tag is ~10% faster
  (shape building happens once per process, so this is a compile-time win).
- Renamed the compiled renderer `Pure\Compile\Compiled` to
  `Pure\Compile\Renderer`; the internal code generator is now
  `Pure\Compile\Internal\CodeGenerator` (previously `Compiler`).
- Shape-tree traversal now lives in a single
  `Pure\Compile\Internal\ShapeWalker` consumed by both the structure fingerprint
  and the code generator, so paths, ordering and map keys cannot drift apart;
  map keys include the path occurrence so duplicate paths (siblings,
  `Slot::if` branches) keep distinct closures.
- `Pure\Core\Slot` types shape arguments against the new
  `Pure\Core\ShapeContract` (implemented by `Pure\Compile\Shape`), so
  `Pure\Core` no longer depends on `Pure\Compile`.
- Slot values are allowed as tag children and attribute values; `Tag::render()`,
  `toPrint()` and `toSave()` throw a `LogicException` for trees containing
  slots, and `toJSON()` describes slots as `['slot' => '<name>']`.
- Every example renders through the compiled path; the classic component
  implementations moved to `bench/fixtures/` as benchmark baselines.
- String children are now escaped instead of tag-filtered: `div('<p>x</p>')`
  renders `&lt;p&gt;x&lt;/p&gt;` and `2<3` / `a<b` are no longer silently
  dropped. Static and bound text behave identically; `rawHtml()` / `rawXml()`
  remain the explicit way to emit trusted markup.
- `SlotRuntime` value coercion takes an inline fast path for scalars and null,
  and `Slot::attr()` handles booleans like `Tag::setAttr()`; compiled rendering
  is ~12% faster on the 600-element benchmark page (139–144 µs → 124–125 µs).
- Generated renderers escape scalar text slots inline with the shared
  `Escaper::FLAGS` / `Escaper::ENCODING` constants and keep
  `SlotRuntime::text()` as the fallback for null, `Stringable` and invalid
  values, so the documented empty-null and `InvalidArgumentException`
  behaviour is unchanged; compiled rendering is another ~5% faster
  (124 µs → 118 µs with opcache). `Compile::CACHE_VERSION` is now 5 because
  the generated code and the layout both changed.
- `Tag::toSave()` and `Renderer::save()` write with `file_put_contents()`, so a
  long document is always written in full.
- Source directories now mirror the namespaces one to one —
  `src/Core`, `src/Compile`, `src/HTML`, `src/SVG`, `src/Utils` — and the
  function frontends live in `src/HTML/functions.php` and
  `src/SVG/functions.php`; the PSR-4 section collapses to a single
  `"Pure\\": "src/"` rule. The `@internal` machinery moved to
  `Pure\Compile\Internal\*` so the frozen surface (`Compile`, `Shape`,
  `Renderer`, `CompileException`) is visible in the tree. Generated renderers
  reference `Pure\Compile\Internal\SlotRuntime`, so caches written by earlier
  versions are discarded and rebuilt.

### Removed

- `Pure\Core\Dom`, `PDom` and `NDom`, and `Tag::toDom()` in favor of string
  rendering and the compiled path.

### Fixed

- SVG camelCase self-closing tags (`animateMotion`, `feBlend`, `feColorMatrix`,
  `feDisplacementMap`, `feDropShadow`, `feGaussianBlur`, `feImage`) are matched
  case-sensitively again, as the API documentation always claimed.
- Bound boolean attribute values via `Slot::attr()` now match static attributes:
  `false` omits the attribute and `true` renders its name as value instead of
  `disabled=""` / `disabled="1"`.
- The structure fingerprint is rebuilt from the live tree on every compile, so a
  shape tree mutated after `id()` or a first compile can no longer generate code
  under a stale id or poison the on-disk cache for an identically shaped tree.
  `Shape::id()` reflects mutations immediately; an already compiled renderer
  still describes the tree state it was compiled from until `Compile::flush()`.
- `Tag::setAttrs()` / `setAttrByCb()` / `getAttr()` normalize `className` and
  underscored keys like the chained setters, and non-stringable attribute
  values raise an `InvalidArgumentException` instead of an "Array to string
  conversion" warning and a literal `"Array"` attribute.
- `class('')` no longer emits `class=""`; it behaves like `class(null)` and
  `class([''])`.
- `Slot::default()` rejects objects, closures and resources with an
  `InvalidArgumentException` instead of failing later with a bare
  `serialize()` error or broken generated code.

[Unreleased]: https://github.com/YonLD/purephp/commits/main
