# Props and Slots

In PurePHP, "props" come in two forms:

- **Static props** — values known while the component is built (function
  arguments, literal attributes).
- **Dynamic props** — values bound at render time: `Slot` placeholders.

This page is the data-binding reference; see
[Compiled Components](/guide/compiled) for the rendering pipeline itself.

## Static Props

### HTML Attributes

Attributes are set with method chaining and are stored in the shape as
literals:

```php
<?php

use Pure\Compile\Compile;

use function Pure\HTML\div;

$shape = Compile::shape(
    div('Content')
        ->id('main')
        ->class('container')
        ->style('background: #fff;')
);

$shape([]);
```

`className()` is an alias of `class()`, and many attributes can be passed to
`class()`:

```php
<?php

div('Content')->class('container', 'mt-4')->id('main');
```

### Data and ARIA Attributes

Attribute names containing hyphens use underscores, because `-` is not valid
in a PHP method name:

```php
<?php

div('Content')
    ->data_id('123')      // data-id="123"
    ->data_type('card')   // data-type="card"
    ->aria_label('Card'); // aria-label="Card"
```

### Boolean Attributes

A `true` value renders the attribute with its own name as value; `false` and
`null` omit it:

```php
<?php

input()->type('checkbox')->checked(true);  // checked="checked"
input()->type('checkbox')->checked(false); // no checked attribute
```

`Slot::attr()` follows the same rules at render time, so static and dynamic
attributes cannot drift apart: a bound `false` omits the attribute and a bound
`true` renders `checked="checked"`.

## Dynamic Props

Dynamic attribute values use `Slot::attr()`. The argument is the data key, not
the attribute name — the attribute name comes from the setter, so
`->class(Slot::attr('classList'))` binds `classList` from the data and writes it
into `class`. A `null` value omits the attribute at render time (a bound `false`
behaves the same), which is also how conditional attributes work:

```php
<?php

use Pure\Core\Slot;

$shape = Compile::shape(
    button('Save')->class(Slot::attr('classList'))->disabled(Slot::attr('disabled'))
);

$shape(['classList' => 'btn btn-primary', 'disabled' => null]);       // <button class="btn btn-primary">Save</button>
$shape(['classList' => 'btn btn-primary', 'disabled' => 'disabled']); // disabled="disabled"
```

## Slot Reference

| Slot | Value | Behavior |
| --- | --- | --- |
| `Slot::text($name)` | stringable or `null` | escaped text content; `null` renders empty |
| `Slot::attr($name)` | stringable or `null` | escaped attribute value ($name is the data key); `null` omits the attribute |
| `Slot::raw($name)` | stringable, `null`, or an iterable of those | emitted verbatim, never escaped; an iterable is concatenated in order |
| `Slot::child($name, $shape)` | array | nested scope for `$shape` |
| `Slot::each($name, $shape)` | iterable of arrays | renders `$shape` per item |
| `Slot::if($name, $then, $else = null)` | truthy check | renders a branch; a missing key is false |
| `Slot::eachKind($name, ['kind' => $shape])` | iterable of arrays | dispatches per item on the discriminator key |

## Modifiers

```php
<?php

use Pure\Core\Slot;

Slot::text('subtitle')->required(false);   // missing key renders as empty
Slot::text('subtitle')->default('—');       // fallback for a missing key
```

- `required(false)` makes a slot optional; its value is then read with `??`
  semantics (`null` when missing).
- `default($value)` provides a fallback for a missing key and makes the slot
  optional. The default is inlined into the compiled renderer, so it must be a
  value type: `null`, a scalar or an array of value types.
- `Slot::if()` rejects both modifiers with a `LogicException`: its condition is
  truthiness with a `false` fallback.

## Value Coercion and Escaping

Text, attribute and raw slots accept `null`, scalars and `Stringable`
objects — including the `Raw` a component returns, which needs no cast. They are
converted to string before use; arrays and other objects raise an
`InvalidArgumentException` naming the full slot path. A raw slot goes one step
further and accepts an iterable of stringable values, concatenating them in
order.

- `Slot::text()` escapes with `htmlspecialchars(..., double_encode: false)`,
  so entities you already escaped (`&copy;`) stay intact.
- `Slot::attr()` escapes with `double_encode: true`.
- `Slot::raw()` performs no escaping — only use it with trusted markup.
- Invalid UTF-8 is substituted with the replacement character instead of
  producing broken output.

## Missing Data

Required slots throw `Pure\Core\MissingSlotException` with the full path:

```php
try {
    $shape([]);
} catch (\Pure\Core\MissingSlotException $e) {
    echo $e->getMessage(); // slot 'items[].title' is required but was not provided.
}
```

Paths identify nested scopes: `card.title` for a child slot, `items[].title` for
a list item, `items[].kind` for a heterogeneous list discriminator.

## Derived Props

A child component reads its props from the nested data under its slot name, so
derive them in the data layer before rendering:

```php
<?php

use Pure\Core\Slot;

$badge = Compile::shape(span(Slot::text('label'))->class('badge'));

$shape = Compile::shape(div(Slot::child('user', $badge)));

$shape(['user' => ['label' => 'ADA']]); // <div><span class="badge">ADA</span></div>
```

A nested shape can be a bare tag tree — `Slot::child('user', span(Slot::text('label')))`
works too; `Compile::shape()` is only needed when the nested tree is built and
memoized separately.

`Slot::each()` and `Slot::eachKind()` read their items the same way: every item
is already the item scope, so a controller turns a list of rows into a list of
prop arrays before handing it to the shape.

## Component Props Contract

Because a shape is data-free, a component's data contract lives in its slots.
Document it next to the component and keep the bindings array in one place; a
missing required key will fail loudly with the full path at render time.

## Next Steps

- [Compiled Components](/guide/compiled) - Lists, conditionals, caching and limitations
- [Core Concepts](/guide/concepts) - Shapes, scopes and compiling
- [Events](/guide/events) - Event attributes and browser-side handlers
