# Utility Functions

PurePHP provides several utility functions to simplify development. These functions are automatically used when setting element attributes.

*`clx()` and `sty()` are unchanged by compiled rendering: use them
while building static attributes in a shape, and bind dynamic values with
`Slot::text()` / `Slot::attr()` / `Slot::raw()` — see
[Compiled Components](/guide/compiled). Most examples below use the tag API,
which remains valid for snippets and debugging.*

## clx Function

The `clx` function is used to merge class names, supporting strings, arrays, and conditional class names.

### Basic Usage

```php
<?php

use function Pure\Utils\clx;

// Merge multiple string class names
$classes = clx('btn', 'btn-primary', 'large');
echo $classes; // Output: btn btn-primary large
```

### Conditional Class Names

```php
<?php

use function Pure\Utils\clx;

$isActive = true;
$isDisabled = false;

$classes = clx(
    'btn',
    $isActive ? 'active' : null,
    $isDisabled ? 'disabled' : null
);
echo $classes; // Output: btn active
```

### Array Support

```php
<?php

use function Pure\Utils\clx;

$classes = clx(
    'btn',
    [
        'btn-primary',
        'active' => true,
        'disabled' => false,
        'large' => null
    ]
);
echo $classes; // Output: btn btn-primary active
```

### Built-in Usage in class() Method

The `class()` method has built-in `clx` function and can accept multiple parameters directly:

```php
<?php

use function Pure\HTML\div;

$isActive = true;
$size = 'large';

div('Content')
    ->class('btn', 'btn-primary', $isActive ? 'active' : null, $size)
    ->print();

// Equivalent to
use function Pure\Utils\clx;

$classes = clx('btn', 'btn-primary', $isActive ? 'active' : null, $size);
div('Content')->class($classes)->print();
```

## sty Function

The `sty` function converts style arrays to CSS strings.

### Basic Usage

```php
<?php

use function Pure\Utils\sty;

$styles = sty([
    'background-color' => 'red',
    'height' => '36px',
    'border' => '1px solid #fff'
]);
echo $styles; // Output: background-color: red; height: 36px; border: 1px solid #fff;
```

### Conditional Styles

```php
<?php

use function Pure\Utils\sty;

$isVisible = true;
$color = 'blue';

$styles = sty([
    'color' => $color,
    'display' => $isVisible ? 'block' : 'none',
    'opacity' => $isVisible ? 1 : 0,
    'margin' => null,  // Will be ignored
    'padding' => false // Will be ignored
]);
echo $styles; // Output: color: blue; display: block; opacity: 1;
```

### Built-in Usage in style() Method

The `style()` method has built-in `sty` function and can accept arrays directly:

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->style([
        'background-color' => '#f0f0f0',
        'padding' => '20px',
        'border-radius' => '8px',
        'margin' => '10px 0'
    ])
    ->print();

// Equivalent to
use function Pure\Utils\sty;

$styles = sty([
    'background-color' => '#f0f0f0',
    'padding' => '20px',
    'border-radius' => '8px',
    'margin' => '10px 0'
]);
div('Content')->style($styles)->print();
```

## Raw Markup

Trusted markup is wrapped in `Pure\Core\Raw::of()`; the tag API emits it
verbatim. See the [Raw API](/api/raw) for details.

## Practical Examples

### Dynamic Button Component

Static configuration is a function argument; the label and the button state
are slots:

```php
<?php

use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\bind;
use function Pure\HTML\button;
use function Pure\Utils\sty;

function ActionButton(
    string $text,
    string $variant = 'primary',
    string $size = 'medium',
    bool $loading = false,
    ?string $style = null
): Raw {
    static $renders = [];

    $render = $renders["{$variant}|{$size}|" . (int) $loading] ??= bind(
        button(Slot::text('text'))
            ->class('btn', "btn-{$variant}", "btn-{$size}", $loading ? 'loading' : null)
            ->style(Slot::attr('style'))
            ->disabled(Slot::attr('disabled'))
    );

    return $render([
        'text' => $text,
        'style' => $style,
        'disabled' => null,
    ]);
}

// Render-time values only; a null attribute is omitted.
echo ActionButton('Submit', 'success', 'large', false, sty(['opacity' => 1, 'cursor' => 'pointer']));
```

### Responsive Card Component

The card accepts an HTML child, so its content is bound with `Slot::raw()`:

```php
<?php

use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\bind;
use function Pure\HTML\{div, h3, p};

function Card(string $title, Raw $content, string $theme = 'light', bool $featured = false): Raw
{
    static $renders = [];

    $render = $renders["{$theme}|" . (int) $featured] ??= bind(
        div(
            h3(Slot::text('title'))->class('card-title'),
            p(Slot::raw('content'))->class('card-content')
        )
        ->class('card', "card-{$theme}", $featured ? 'card-featured' : null)
        ->style([
            'border-width' => $featured ? '2px' : '1px',
            'box-shadow' => $featured ? '0 4px 12px rgba(0,0,0,0.15)' : '0 2px 4px rgba(0,0,0,0.1)',
            'background-color' => $theme === 'dark' ? '#333' : '#fff',
            'color' => $theme === 'dark' ? '#fff' : '#333'
        ])
    );

    return $render(['title' => $title, 'content' => $content]);
}

// `content` is trusted HTML, emitted verbatim.
echo Card('Featured Card', Raw::of('<strong>This is the content</strong> of a featured card'), 'dark', true);
```

## Next Steps

- [Basic Usage](/guide/basic-usage) - Learn basic syntax and usage
- [Components](/guide/components) - Learn how to create and use components
- [TailwindCSS Integration](/guide/tailwindcss) - Learn how to use with TailwindCSS
