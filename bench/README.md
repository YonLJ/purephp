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

# bootstrap-features page: classic baseline (bench/fixtures) vs page shape
php examples/bootstrap-features/bench.php [iterations]

# with opcache
php -d opcache.enable_cli=1 bench/compare.php

# with opcache + JIT
php -d opcache.enable_cli=1 -d opcache.jit=1255 -d opcache.jit_buffer_size=64M bench/compare.php
```

Scripts that render both paths (`bench/compare.php` and
`examples/bootstrap-features/bench.php`) assert byte-identical output and print
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

### `examples/bootstrap-features/bench.php` — 3000 iterations

| Path | no opcache | opcache | opcache + JIT |
| --- | --- | --- | --- |
| classic build + `render()` | 188–204 µs | 183.6 µs | 147.8 µs |
| compiled shape + data | 25–33 µs | 24.9 µs | 22.6 µs |
| speedup | 6.2–8.3× | 7.4× | 6.6× |

### `bench/cache.php` — compile only, one page shape

| Phase | Time |
| --- | --- |
| cold (generate + write cache file) | ~640–780 µs |
| warm (read + `require` cached renderer) | ~340–480 µs |

The remaining warm cost is the structure fingerprint walk plus loading the
generated file; the shape build itself (~0.8 ms for this page) dominates
per-process startup either way.

## Notes

