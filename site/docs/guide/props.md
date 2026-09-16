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

Dynamic attribute values use `Slot::attr()`. A `null` value omits the
attribute at render time (a bound `false` behaves the same), which is also how
conditional attributes work:

```php
<?php

use Pure\Core\Slot;

$shape = Compile::shape(
    button('Save')->class(Slot::attr('class'))->disabled(Slot::attr('disabled'))
);

$shape(['class' => 'btn btn-primary', 'disabled' => null]);       // <button class="btn btn-primary">Save</button>
$shape(['class' => 'btn btn-primary', 'disabled' => 'disabled']); // disabled="disabled"
```

## Slot Reference

| Slot | Value | Behavior |
| --- | --- | --- |
| `Slot::text($name)` | stringable or `null` | escaped text content; `null` renders empty |
| `Slot::attr($name)` | stringable or `null` | escaped attribute value; `null` omits the attribute |
| `Slot::raw($name)` | stringable or `null` | emitted verbatim, never escaped |
| `Slot::sub($name, $shape)` | array | nested scope for `$shape` |
| `Slot::each($name, $shape)` | iterable of arrays | renders `$shape` per item |
| `Slot::if($name, $then, $else = null)` | truthy check | renders a branch; a missing key is false |
| `Slot::eachAny($name, ['kind' => $shape])` | iterable of arrays | dispatches per item on the discriminator key |

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
objects. They are converted to string before use; arrays and other objects
raise an `InvalidArgumentException` naming the full slot path.

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

Paths identify nested scopes: `card.title` for a sub slot, `items[].title` for
a list item, `items[].kind` for a heterogeneous list discriminator.

## Derived Props (Maps)

A map closure derives the nested scope of a child component instead of reading
`$data[$name]` directly:

```php
<?php

use Pure\Core\Slot;

$badge = Compile::shape(span(Slot::text('label'))->class('badge'));

$shape = Compile::shape(div(
    Slot::sub('user', $badge, static fn (array $data): array => [
        'label' => strtoupper((string)$data['name']),
    ])
));

$shape(['name' => 'ada']); // <div><span class="badge">ADA</span></div>
```

`Slot::each()` and `Slot::eachAny()` accept the same optional map, applied to
every item.

## Component Props Contract

Because a shape is data-free, a component's data contract lives in its slots.
Document it next to the component and keep the bindings array in one place; a
missing required key will fail loudly with the full path at render time.

## Next Steps

- [Compiled Components](/guide/compiled) - Lists, conditionals, caching and limitations
- [Core Concepts](/guide/concepts) - Shapes, scopes and compiling
- [Events](/guide/events) - Event attributes and browser-side handlers
