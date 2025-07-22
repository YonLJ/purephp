# Basic Usage

This guide introduces the core concepts and basic usage of PurePHP.

## Basic Syntax

### 1. Creating HTML Elements

PurePHP uses function calls to create HTML elements:

```php
<?php

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\p;

// Create a simple div element
div('Hello World')->toPrint();

// Create nested elements
div(
    h1('Title'),
    p('Paragraph content')
)->class('container')->toPrint();
```

### 2. Setting Attributes

Use method chaining to set element attributes:

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->class('container')
    ->style('background: #fff;')
    ->data_key('primary')
    ->id('main')
    ->toPrint();
```

## Important Usage Notes

### 1. className Alias

Since `class` is a PHP keyword, PurePHP provides `className` as an alias:

```php
<?php

use function Pure\HTML\div;

// Both ways work
div('Content')->class('container')->toPrint();
div('Content')->className('container')->toPrint();
```

### 2. Built-in Utility Functions

The `class` method has built-in `clx` function, and `style` method has built-in `sty` function to handle arrays and conditional parameters:

```php
<?php

use function Pure\HTML\div;

$isActive = true;
$isLarge = false;

// class method automatically uses clx
div('Content')
    ->class('btn', $isActive ? 'active' : null, $isLarge ? 'large' : null)
    ->style(['color' => 'red', 'font-size' => '16px'])
    ->toPrint();

// Equivalent to manually using utility functions
use function Pure\Utils\{clx, sty};

$classes = clx('btn', $isActive ? 'active' : null, $isLarge ? 'large' : null);
$styles = sty(['color' => 'red', 'font-size' => '16px']);

div('Content')
    ->class($classes)
    ->style($styles)
    ->toPrint();
```

### 3. Attribute Naming Rules

Since `-` has special meaning in PHP, attributes like `data-id` need to be written as `data_id`:

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->data_id('123')           // corresponds to data-id="123"
    ->data_type('card')        // corresponds to data-type="card"
    ->aria_label('Button')     // corresponds to aria-label="Button"
    ->toPrint();
```

### 3. Adding Child Elements

You can add multiple child elements through parameters:

```php
<?php

use function Pure\HTML\div;
use function Pure\HTML\p;

div(
    p('First paragraph'),
    p('Second paragraph'),
    p('Third paragraph')
)->class('content')->toPrint();
```

## Common HTML Tags

PurePHP supports all common HTML tags:

```php
<?php

use function Pure\HTML\{
    div, span, p, h1, h2, h3, h4, h5, h6,
    a, img, ul, ol, li, table, tr, td, th,
    form, input, button, textarea, select, option
};

// Create a link
a('Click here')->href('https://example.com')->toPrint();

// Create an image
img()->src('image.jpg')->alt('Image description')->toPrint();

// Create a list
ul(
    li('Item 1'),
    li('Item 2'),
    li('Item 3')
)->class('list')->toPrint();

// Create a form
form(
    input()->type('text')->name('username'),
    input()->type('password')->name('password'),
    button('Submit')->type('submit')
)->method('POST')->action('/login')->toPrint();
```

## SVG Support

PurePHP has built-in support for SVG tags:

```php
<?php

use function Pure\SVG\{svg, circle, rect, path};

// Create a simple circle
svg(
    circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->stroke('black')
        ->stroke_width('3')
        ->fill('red')
)->width('100')->height('100')->toPrint();

// Create a rectangle
svg(
    rect()
        ->x('10')
        ->y('10')
        ->width('80')
        ->height('80')
        ->fill('blue')
)->width('100')->height('100')->toPrint();
```

## Conditional Rendering

Use PHP conditional statements for conditional rendering:

```php
<?php

use function Pure\HTML\{div, p};

$isLoggedIn = true;

div(
    $isLoggedIn ? p('Welcome back!') : p('Please log in')
)->class('message')->toPrint();
```

## Loop Rendering

Use PHP loop statements to render lists:

```php
<?php

use function Pure\HTML\{ul, li};

$items = ['Apple', 'Banana', 'Orange'];

ul(
    ...array_map(fn($item) => li($item), $items)
)->class('fruits')->toPrint();
```

## Style Handling

### 1. Inline Styles

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->style('
        background: #f0f0f0;
        padding: 20px;
        border-radius: 8px;
    ')
    ->toPrint();
```

### 2. Class Name Handling

```php
<?php

use function Pure\HTML\div;

$isActive = true;

div('Content')
    ->class('container')
    ->class($isActive ? 'active' : 'inactive')
    ->toPrint();
```

## Next Steps

- [Utility Functions](/guide/utils) - Learn about built-in utility functions
- [Components](/guide/components) - Learn how to create and use components
- [TailwindCSS Integration](/guide/tailwindcss) - Learn how to use with TailwindCSS
