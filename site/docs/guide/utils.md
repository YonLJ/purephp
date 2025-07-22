# Utility Functions

PurePHP provides several utility functions to simplify development. These functions are automatically used when setting element attributes.

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
    ->toPrint();

// Equivalent to
use function Pure\Utils\clx;

$classes = clx('btn', 'btn-primary', $isActive ? 'active' : null, $size);
div('Content')->class($classes)->toPrint();
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
    ->toPrint();

// Equivalent to
use function Pure\Utils\sty;

$styles = sty([
    'background-color' => '#f0f0f0',
    'padding' => '20px',
    'border-radius' => '8px',
    'margin' => '10px 0'
]);
div('Content')->style($styles)->toPrint();
```

## rawHtml Function

The `rawHtml` function is used to insert raw HTML content without escaping.

### Basic Usage

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

div(
    rawHtml('<strong>This is bold text</strong>'),
    rawHtml('<em>This is italic text</em>')
)->toPrint();
```

### Security Considerations

When using `rawHtml`, ensure the content is safe to avoid XSS attacks:

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// Safe usage
$safeHtml = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
div(rawHtml($safeHtml))->toPrint();

// Or use known safe HTML
$iconHtml = '<svg><path d="..."/></svg>';
div(rawHtml($iconHtml))->toPrint();
```

## Practical Examples

### Dynamic Button Component

```php
<?php

use function Pure\HTML\button;

function Button($props) {
    [
        'text' => $text,
        'variant' => $variant = 'primary',
        'size' => $size = 'medium',
        'disabled' => $disabled = false,
        'loading' => $loading = false
    ] = $props;

    return button($loading ? 'Loading...' : $text)
        ->class(
            'btn',
            "btn-{$variant}",
            "btn-{$size}",
            $disabled ? 'disabled' : null,
            $loading ? 'loading' : null
        )
        ->style([
            'opacity' => $disabled ? 0.6 : 1,
            'cursor' => $disabled ? 'not-allowed' : 'pointer'
        ])
        ->disabled($disabled);
}

// Usage example
Button([
    'text' => 'Submit',
    'variant' => 'success',
    'size' => 'large',
    'loading' => false
])->toPrint();
```

### Responsive Card Component

```php
<?php

use function Pure\HTML\{div, h3, p};

function Card($props) {
    [
        'title' => $title,
        'content' => $content,
        'featured' => $featured = false,
        'theme' => $theme = 'light'
    ] = $props;

    return div(
        h3($title)->class('card-title'),
        p($content)->class('card-content')
    )
    ->class(
        'card',
        "card-{$theme}",
        $featured ? 'card-featured' : null
    )
    ->style([
        'border-width' => $featured ? '2px' : '1px',
        'box-shadow' => $featured ? '0 4px 12px rgba(0,0,0,0.15)' : '0 2px 4px rgba(0,0,0,0.1)',
        'background-color' => $theme === 'dark' ? '#333' : '#fff',
        'color' => $theme === 'dark' ? '#fff' : '#333'
    ]);
}

// Usage example
Card([
    'title' => 'Featured Card',
    'content' => 'This is the content of a featured card',
    'featured' => true,
    'theme' => 'dark'
])->toPrint();
```

## Next Steps

- [Basic Usage](/guide/basic-usage) - Learn basic syntax and usage
- [Components](/guide/components) - Learn how to create and use components
- [TailwindCSS Integration](/guide/tailwindcss) - Learn how to use with TailwindCSS
