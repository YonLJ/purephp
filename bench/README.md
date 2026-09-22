# Benchmarks

Manual benchmarks for the compiled renderer. They are **not** part of CI; run
them before/after changes to the compiler or the render path.

## Commands

```bash
# 600-element page: build+render vs compiled shape vs static literal
php bench/compare.php [--rows=200] [--iters=3000]

# on-disk renderer cache: cold (generate) then warm (load) in two processes
php bench/cache.php --clear
php bench/cache.php

# precompiled artifact: write it, then load cold/warm and render in a new process
php bench/artifact.php --write
php bench/artifact.php [iterations]

# features page of examples/bootstrap: tag-tree baseline (bench/fixtures) vs the
# page function, the precompiled features.pure.php artifact and the plain view
php examples/bootstrap/bench.php [iterations]

# component loading: per-unit artifact requires, cold and with a warm opcache
php bench/registry.php
php -d opcache.enable_cli=1 bench/registry.php

# with opcache
php -d opcache.enable_cli=1 bench/compare.php

# with opcache + JIT
php -d opcache.enable_cli=1 -d opcache.jit=1255 -d opcache.jit_buffer_size=64M bench/compare.php
```

Scripts that render both paths (`bench/compare.php` and
`examples/bootstrap/bench.php`) assert byte-identical output and print
`identical=yes`; `bench/cache.php` measures cold and warm compile times only.

## Recorded Numbers

Machine: AMD Ryzen 5 7500F, Linux, PHP 8.4.24 CLI (NTS, opcache 8.4.24).
Recorded 2026-09-16. Sections below that say "the i5-1135G7 box" were recorded
on a second machine (PHP 8.1.34, 2026-09-20); every number here is a record of
one run, not a promise about yours.

### `bench/compare.php` — 200 rows / ~604 elements, 3000 iterations

| Path | no opcache | opcache | opcache + JIT |
| --- | --- | --- | --- |
| build tree + `Tag::render()` | 631.0 µs | 608.3 µs | 433.8 µs |
| compiled shape + data | 141.7 µs | 135.2 µs | 107.9 µs |
| render only (tree reused) | 224.2 µs | 212.4 µs | 163.5 µs |
| compiled static tree (literal) | 0.1 µs | 0.1 µs | 0.2 µs |
| end-to-end speedup | 4.5× | 4.5× | 4.0× |

The same script on a slower box (i5-1135G7, PHP 8.1.34, recorded 2026-09-20)
reads 1187.3 / 178.9 / 344.1 / 0.1 µs for the four rows, for 6.6× end to end
without opcache, 5.5× with it and 4.6× with the JIT. The µs columns are
machine-local; the ordering and the ~4–7× ratio are what carries over.

### `bench/artifact.php` — one page shape: build+compile vs requiring its artifact

| Phase | Time |
| --- | --- |
| shape: build tree + compile | 1.7–2.9 ms |
| artifact: first `require` | 38–67 µs |
| artifact: warm `require` | 25 µs |
| render per call, either path | 1.4–2.5 µs |

Run `php bench/artifact.php --write` once, then `php bench/artifact.php`; the
second run of the pair is what the table calls warm. Pass `--write` again after
changing the compiler, or the script reports `id match: NO` for the artifact it
left in `/tmp` (its output still matches, only the fingerprint differs).

### `examples/bootstrap/bench.php` — 3000 iterations

Recorded 2026-09-20 on the i5-1135G7 box above (PHP 8.1.34), for the
function-component example:

| Path | no opcache |
| --- | --- |
| build tree + `Tag::render()` | 339.3 µs |
| page function (components + artifact) | 104 µs |
| plain view + bindings | 20 µs |
| page vs tag tree | 2.8–3.3× |

The rows recorded for the previous shape-based example (188–204 µs for the tag tree,
25–33 µs compiled, 6.2–8.3×) no longer apply: the body is composed by component
functions now, so the page no longer renders as one compiled shape.

The benchmark also renders the precompiled `views/features.pure.php` artifact and
the `views/features.plain.php` plain view of `examples/bootstrap`, and asserts
both match the in-process document shape byte for byte (the artifact with the
same id). Artifacts read every slot through `TemplateRuntime`, so their render
carries one extra call per value while skipping the shape build and compile
entirely; plain views inline `htmlspecialchars()` and are the fastest flavor,
but they are includes, so their row only pays off with opcache enabled (the
benchmark ages the freshly compiled view, because opcache revalidates a file
whose mtime just changed on every include).

Note that the `skeleton artifact + bindings` row the script prints (~1.5–1.9 µs)
is **not** a page render: it binds already-rendered component markup and prints
the page template around it, which is why it is two orders of magnitude below
the page function row.

### `bench/cache.php` — compile only, the features page skeleton

Measures the page template of `examples/bootstrap/views/features.cmp.php`, the
unit the example precompiles with `pure compile`. The body of the page is
composed by the component functions and is not part of this shape, so this is
the skeleton only.

| Phase | Time |
| --- | --- |
| cold (generate + write cache file) | ~0.78 ms |
| warm (read + `require` cached renderer) | ~0.26 ms |

The remaining warm cost is the structure fingerprint walk plus loading the
generated file; the shape build itself dominates per-process startup either
way. (Recorded 2026-09-20 on the i5-1135G7 box above, PHP 8.1.34; re-run the
two commands above to record your machine.)

### `bench/registry.php` — component loading, 22 unit artifacts

| opcache | cold | warm (second require in the process) |
| --- | --- | --- |
| off | 667–702 µs | 360–414 µs (16.4–18.8 µs each) |
| on | 1.4–1.7 ms | **10–14 µs (0.5–0.6 µs each)** |

Recorded 2026-09-20 on the i5-1135G7 box above (PHP 8.1.34). Every artifact now
carries a one-line cache-version guard, which costs about a microsecond per
artifact per cold require without opcache and nothing measurable with opcache;
an earlier recording of this table predates it. A multi-line guard that
concatenates the installed version into its message measured ~5 µs per artifact
instead, which is why the generated line is short and its message is static.

Reading each artifact's header instead of requiring
it measured ~4 µs per file, and hashing every unit ~7 µs, against ~0.5 µs to
load the artifact with opcache: validating content costs more than compiling it.

## Notes

- Per-request note: function statics reset under standard PHP-FPM, so the
  compiled-path win in these tables applies to long-running workers, or to
  PHP-FPM with `Compile::cachePath()` loading a warm cache. A fresh process
  pays the shape build plus compile cost on every request — which is exactly
  what precompiled artifacts avoid.
- The compiled path is byte-identical to `Tag::render()`; `compare.php` and the
  example benchmark assert it.
- Subtrees without slots are folded into literals at compile time, which is
  why fully static trees render in well under a microsecond.
- The bootstrap page has many dynamic slots, so its per-render cost is
  dominated by slot binding, not by static markup. Composed from component
  functions it measures ~104 µs/op for the page function, ~20 µs/op for the
  plain view; the ≤15 µs target set for the earlier single-shape page was
  reached only by the plain view, while the fully static case is effectively
  free.
- A page function pays for every child component call in the request: the
  benchmark's `artifact vs page` ratio (0.01–0.02×) says how much of the page
  cost is the template itself, and the rest is composing the components. A page
  rendered as one precompiled artifact, without that composition, costs about
  half as much per request as the same page built from component functions.
