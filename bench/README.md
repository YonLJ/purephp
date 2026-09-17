# Benchmarks

Manual benchmarks for the compiled renderer. They are **not** part of CI; run
them before/after changes to the compiler or the render path.

## Commands

```bash
# 600-element page: classic build+render vs compiled shape vs static literal
php bench/compare.php [--rows=200] [--iters=3000]

# on-disk renderer cache: cold (generate) then warm (load) in two processes
php bench/cache.php --clear
php bench/cache.php

# precompiled artifact: write it, then load cold/warm and render in a new process
php bench/artifact.php --write
php bench/artifact.php [iterations]

# features page of examples/bootstrap: classic baseline (bench/fixtures) vs shape vs
# precompiled index.pure.php artifact
php examples/bootstrap/bench.php [iterations]

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
Recorded 2026-09-16.

### `bench/compare.php` — 200 rows / ~604 elements, 3000 iterations

| Path | no opcache | opcache | opcache + JIT |
| --- | --- | --- | --- |
| build tree + `render()` | 631.0 µs | 608.3 µs | 433.8 µs |
| compiled shape + data | 141.7 µs | 135.2 µs | 107.9 µs |
| render only (tree reused) | 224.2 µs | 212.4 µs | 163.5 µs |
| compiled static tree (literal) | 0.1 µs | 0.1 µs | 0.2 µs |
| end-to-end speedup | 4.5× | 4.5× | 4.0× |

### `examples/bootstrap/bench.php` — 3000 iterations

| Path | no opcache | opcache | opcache + JIT |
| --- | --- | --- | --- |
| classic build + `render()` | 188–204 µs | 183.6 µs | 147.8 µs |
| compiled shape + data | 25–33 µs | 24.9 µs | 22.6 µs |
| speedup | 6.2–8.3× | 7.4× | 6.6× |

The benchmark also renders the precompiled `index.pure.php` artifact and the
`features.plain.php` plain view of `examples/bootstrap`, and asserts both
match the in-process document shape byte for byte (the artifact with the same
id). Artifacts read every slot through `TemplateRuntime`, so their render
carries one extra call per value while skipping the shape build and compile
entirely; plain views inline `htmlspecialchars()` and are the fastest flavor,
but they are includes, so their row only pays off with opcache enabled (the
benchmark ages the freshly compiled view, because opcache revalidates a file
whose mtime just changed on every include).

### `bench/cache.php` — compile only, one page shape

| Phase | Time |
| --- | --- |
| cold (generate + write cache file) | ~640–780 µs |
| warm (read + `require` cached renderer) | ~340–480 µs |

The remaining warm cost is the structure fingerprint walk plus loading the
generated file; the shape build itself (~0.8 ms for this page) dominates
per-process startup either way.

## Notes

- Per-request note: function statics reset under standard PHP-FPM, so the
  compiled-path win in these tables applies to long-running workers, or to
  PHP-FPM with `Compile::cachePath()` loading a warm cache. A fresh process
  pays the shape build plus compile cost on every request — which is exactly
  what precompiled artifacts avoid.
- The compiled path is byte-identical to `render()`; `compare.php` and the
  example benchmark assert it.
- Subtrees without slots are folded into literals at compile time, which is
  why fully static trees render in well under a microsecond.
- The bootstrap page has many dynamic slots, so its per-render cost is
  dominated by slot binding, not by static markup; the plan's ≤15 µs target for
  it was not reached (~22 µs), while the fully static case is effectively free.
