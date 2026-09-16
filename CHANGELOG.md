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
- Structure fingerprints (`Shape::id()` / `Renderer::id()`) computed without
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

- Renamed the compiled renderer `Pure\Compile\Compiled` to
  `Pure\Compile\Renderer`; the internal code generator is now
  `Pure\Compile\CodeGenerator` (previously `Compiler`).
- Shape-tree traversal now lives in a single `Pure\Compile\ShapeWalker`
  consumed by both the structure fingerprint and the code generator, so paths,
  ordering and map keys cannot drift apart; map keys include the path
  occurrence so duplicate paths (siblings, `Slot::if` branches) keep distinct
  closures.
- `Pure\Core\Slot` types shape arguments against the new
  `Pure\Core\ShapeContract` (implemented by `Pure\Compile\Shape`), so
  `Pure\Core` no longer depends on `Pure\Compile`.
- `Compile::CACHE_VERSION` is now 2: fingerprints changed, so cached renderers
  written by earlier versions are discarded and regenerated.
- `Pure\Core\Escaper` is now the single source of escaping flags/encoding for
  both the string renderer and the compiled renderer; `Values` uses the same
  constants to avoid an extra call per slot.
- Slot values are allowed as tag children and attribute values; `Tag::render()`,
  `toPrint()` and `toSave()` throw a `LogicException` for trees containing
  slots, and `toJSON()` describes slots as `['slot' => '<name>']`.
- Every example renders through the compiled path; the classic component
  implementations moved to `bench/fixtures/` as benchmark baselines.

### Removed

- `Pure\Core\Dom`, `PDom` and `NDom`, and `Tag::toDom()` in favor of string
  rendering and the compiled path.

[Unreleased]: https://github.com/YonLD/purephp/commits/main
