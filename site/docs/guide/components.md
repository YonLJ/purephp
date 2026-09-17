# Components

Components are the building blocks of a PurePHP UI. A component is a PHP
function that returns a `Shape`; it is compiled once per process (a
long-running worker; under standard PHP-FPM enable `Compile::cachePath()` so
requests load the compiled renderer) and rendered as many times as needed with
different data.

## Function Components

A component takes static configuration as function arguments and describes
dynamic values with slots. Memoize the shape in a `static` variable so the
compile step happens once per process:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, p};

function CardShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h2(Slot::text('title')),
            p(Slot::text('content'))
        )->class('card')
    );
}

// Render the component with data
CardShape()->print([
    'title' => 'Title',
    'content' => 'Content',
]);
```

Never call `Compile::shape()` inside a request handler; the guard
(`Compile::guard(true)`) warns when a call site builds shapes repeatedly.

## Component Props

### 1. Static Props

Static props become function arguments. Memoize per argument value so each
variant gets its own shape:

```php
<?php

function CardShape(string $classList = 'card'): Shape
{
    static $shapes = [];

    return $shapes[$classList] ??= Compile::shape(
        div(
            h2(Slot::text('title')),
            p(Slot::text('content'))
        )->class($classList)
    );
}

CardShape('card shadow')->print([
    'title' => 'Shadowed',
    'content' => 'Static props are function arguments',
]);
```

### 2. Dynamic Props

Dynamic props are slots, bound at render time:

```php
<?php

$shape = Compile::shape(
    button(Slot::text('label'))->type('button')->class(Slot::attr('class'))
);

$shape(['label' => 'Save', 'class' => 'btn btn-primary']);
```

### 3. Event Props

Event handlers are static attributes on the tag (`->onclick(...)`,
`->onchange(...)`); the browser-side handler is identified by its name, so it
is part of the shape:

```php
<?php

$shape = Compile::shape(
    button(Slot::text('label'))->onclick('handleClick()')
);

$shape(['label' => 'Click me']);
```

## Child Components

`Slot::child()` embeds another shape and creates a nested data scope for it:

```php
<?php

function IconShape(string $class = 'icon'): Shape
{
    static $shapes = [];

    return $shapes[$class] ??= Compile::shape(
        span(Slot::attr('glyph'))->class($class)
    );
}

function ButtonShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        button(
            Slot::child('icon', IconShape()),
            Slot::text('label')
        )->class('btn')
    );
}

ButtonShape()->print([
    'icon' => ['glyph' => '+'],
    'label' => 'Add',
]);
```

When a child component needs a different data shape than its parent, pass a map
closure as the third argument; it derives the child scope from the parent data:

```php
<?php

Slot::child('user', BadgeShape(), static fn (array $data): array => [
    'label' => strtoupper((string)$data['name']),
]);
```

## Lists

`Slot::each()` renders a child shape for every item:

```php
<?php

$row = Compile::shape(li(Slot::text('label')));
$list = Compile::shape(ul(Slot::each('rows', $row))->class('list'));

$list(['rows' => [['label' => 'a'], ['label' => 'b']]]);
```

Each item becomes the data scope of the child shape; missing keys follow the
usual rules (`default()`, `required(false)`, or `MissingSlotException`).

## Conditional Rendering

`Slot::if()` renders a branch based on the truthiness of a data key. A missing
key is simply false — it never throws — and the branches share the current
scope:

```php
<?php

$shape = Compile::shape(
    div(
        Slot::if('admin', Compile::shape(span('Administrator')), Compile::shape(span('Guest')))
    )
);

$shape(['admin' => true]);  // <div><span>Administrator</span></div>
$shape([]);                 // <div><span>Guest</span></div>
```

## Mixed Lists

`Slot::eachKind()` dispatches each item on a discriminator key (default
`kind`):

```php
<?php

$shape = Compile::shape(div(Slot::eachKind('blocks', [
    'text' => Compile::shape(p(Slot::text('value'))),
    'link' => Compile::shape(a(Slot::text('value'))->href(Slot::attr('href'))),
])));

$shape(['blocks' => [
    ['kind' => 'text', 'value' => 'hello'],
    ['kind' => 'link', 'value' => 'docs', 'href' => '/docs'],
]]);
```

An item without the discriminator or with an unknown kind raises an
`InvalidArgumentException` naming the full path (`blocks[].kind`).

## Component Composition

Components compose by nesting shapes — either directly in a parent shape or
through `Slot::child()`:

```php
<?php

function PageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        main(
            Slot::child('header', HeaderShape()),
            Slot::each('cards', CardShape())
        )->class('page')
    );
}
```

Because a shape is data-free, a component shape can be reused in many pages at
no extra cost: it is compiled once and inlined into each parent compiler.

## Immediate Rendering (Snippets)

For one-off fragments you can skip shapes entirely and render a tag tree
directly:

```php
<?php

use function Pure\HTML\{div, h2, p};

div(h2('Title'), p('Content'))->class('card')->print();
```

Use this for snippets and debugging only; production pages should compile
shapes so escaping and structure costs are paid once.

## Next Steps

- [Compiled Components](/guide/compiled) - Caching, guard and limitations
- [Props and Slots](/guide/props) - The complete data-binding reference
- [Events](/guide/events) - Event attributes and browser-side handlers
