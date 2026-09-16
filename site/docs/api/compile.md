# Compiled Rendering

`Pure\Compile\Compile` compiles a data-free **shape** tree into a flat PHP renderer.
Static markup is escaped once at compile time and emitted as literal string chunks, so
rendering a page costs little more than string concatenation plus escaping of the
dynamic values.

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\li;
use function Pure\HTML\p;
use function Pure\HTML\span;
use function Pure\HTML\ul;

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

Output is **byte-identical** to `Tag::render()` for the same tree, because both paths share
the same escaping implementation (`Pure\Core\Escaper`, `@internal`).

## Shape vs. Data

A shape is a normal tag tree in which dynamic values are replaced by `Slot` placeholders.
Shapes must not contain request data, and must be built **once per process** — put them in a
`static` variable inside a component function, never inside a request handler.

| Classic component | Compiled component |
| --- | --- |
| `function Card(array $props): HTML` | `function CardShape(): Shape` |
| `h2($title)` | `h2(Slot::text('title'))` |
| `->class($classList)` | `->class($classList)` for static props, `->class(Slot::attr('classList'))` for dynamic ones |
| `array_map(fn ($row) => Row($row), $rows)` | `Slot::each('rows', RowShape())` |
| `<Child($props)>` | `Slot::sub('child', ChildShape())` or a component map |

Static child components need no slot at all — build them inside the shape and they are
compiled into literals:

```php
$shape = Compile::shape(div(Header(), Slot::each('rows', $row))->class('page'));
```

## Slot Types

| Constructor | Value | Behavior |
| --- | --- | --- |
| `Slot::text($name)` | stringable or `null` | coerced to string, escaped; `null` renders as empty content |
| `Slot::attr($name)` | stringable or `null` | escaped attribute value; `null` omits the attribute (same as `setAttr(null)`) |
| `Slot::raw($name)` | stringable or `null` | emitted verbatim, never escaped |
| `Slot::sub($name, $shape)` | array | nested data scope for `$shape` |
| `Slot::each($name, $shape)` | iterable of arrays | renders `$shape` for every item |

Modifiers:

- `->required(false)` — the slot may be missing.
- `->default($value)` — fallback used when the key is missing.
- `Slot::sub($name, $shape, $map)` / `Slot::each($name, $shape, $map)` — derive the nested
  scope with a closure instead of reading `$data[$name]`; this is how a component maps its
  own props to a child component (for example `fn (array $d) => ['href' => '#' . $d['icon']]`).

Values must be stringable: `null`, scalars, and `Stringable` are accepted; arrays and other
objects raise an `InvalidArgumentException` naming the full slot path.

## Errors

- Missing required slot: `Pure\Core\MissingSlotException` with the full path, for example
  `slot 'items[].title' is required but was not provided.`
- Wrong placement (`Slot::attr` as a child, `Slot::text` as an attribute value) or a missing
  shape: `LogicException` at compile time.
- Non-iterable list, non-array sub scope: `InvalidArgumentException` at render time.

## Compiled API

```php
$compiled = $shape->compile();

$compiled($data);                 // string
$compiled->print($data);          // echo
$compiled->save($path, $data);    // write to file, returns bytes written
$compiled->source();              // generated PHP source, useful when debugging
$compiled->id();                  // stable sha1 of the generated source
```

`$shape->id()` is handy as a cache key when a component shape depends on a sub-shape.

## Trees with Slots Cannot Use Other Output Paths

`render()`, `toDom()`, `toPrint()` and `toSave()` throw a `LogicException` for trees that
contain slots, because there is no data to bind. `toJSON()` describes slots as
`['slot' => 'name']`.

## Performance

Measured on PHP 8.1 (603-element page, 200 rows):

| Path | Time per render |
| --- | --- |
| build tree + `render()` | ~950 µs |
| compiled shape + data | ~235 µs |
| compiled static tree (literal) | < 1 µs |

The bootstrap features example is byte-identical between `app.php` and `app-compiled.php`
and renders **6.9× faster** (`php examples/bootstrap-features/bench.php`).

## Limitations

- Data-dependent *structure* (conditionals, varying nesting) is not supported yet; use
  `Slot::each` with a map to normalize items, or fall back to the classic `render()` path.
- Shapes are per-process artifacts: with PHP-FPM they are rebuilt (and recompiled) once per
  worker process, which is negligible compared to the per-request win.
- Compiled renderers trade compilation for speed: compiling a shape that is rendered only
  once per process is slower than `render()`. Compile pages and components that are rendered
  repeatedly.
