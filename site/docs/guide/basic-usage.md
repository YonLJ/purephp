# Basic Usage

This guide introduces the core concepts and basic usage of PurePHP.

## Basic Syntax

### 1. Creating HTML Elements

PurePHP provides multiple ways to create HTML elements:

#### Function Approach (Recommended for predefined tags)

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

#### Magic Static Methods (For custom tags)

```php
<?php

use Pure\Core\HTML;

// Create custom HTML elements using magic methods
HTML::customTag('Custom content')->class('custom')->toPrint();

// Perfect for web components or non-standard tags
HTML::myComponent(
    HTML::header('Component Header'),
    HTML::content('Component Body')
)->data_component('my-component')->toPrint();
```

#### Constructor Method (For performance-critical code)

```php
<?php

use Pure\Core\HTML;

// Direct constructor approach
(new HTML('div', ['Hello World']))->class('container')->toPrint();

// Better performance for large documents
$elements = [];
for ($i = 0; $i < 1000; $i++) {
    $elements[] = new HTML('item', ["Item $i"]);
}
(new HTML('list', $elements))->class('large-list')->toPrint();
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

## Choosing the Right Approach

### When to Use Each Method

#### Use Functions (Recommended for most cases)
- **Best for**: Standard HTML tags, everyday development
- **Advantages**: Clean syntax, good performance, excellent readability
- **Example**: `div()`, `p()`, `span()`, etc.

#### Use Magic Static Methods
- **Best for**: Custom tags, web components, dynamic tag names
- **Advantages**: Works with any tag name, elegant syntax
- **Example**: `HTML::customElement()`, `HTML::webComponent()`

#### Use Constructor
- **Best for**: Performance-critical code, libraries, large documents
- **Advantages**: Maximum performance, explicit type checking
- **Example**: `new HTML('tag')` for thousands of elements

```php
<?php

use function Pure\HTML\div;
use Pure\Core\HTML;

// Function approach - recommended for standard tags
$standard = div('Standard content')->class('container');

// Magic method - perfect for custom tags
$custom = HTML::myCustomTag('Custom content')->data_component('special');

// Constructor - best for performance
$performant = new HTML('div', ['Performance content']);
```

## Important Usage Notes

### 1. String Content vs Raw Content

⚠️ **Important**: When passing string content that contains HTML/XML tags, the tags will be automatically stripped for security:

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// ❌ HTML tags in strings are stripped
div('<p>This will be filtered</p>')->toPrint();
// Output: <div>This will be filtered</div>

// ✅ Use rawHtml to preserve HTML content
div(rawHtml('<p>This will be preserved</p>'))->toPrint();
// Output: <div><p>This will be preserved</p></div>
```

**Why this matters:**
- **Security**: Prevents XSS attacks from user input
- **Predictability**: Ensures consistent behavior
- **Intentionality**: Forces explicit choice for raw content

**When to use rawHtml/rawXml:**
- Including pre-formatted HTML/XML content
- Embedding templates or external content
- Working with trusted HTML/XML strings
- Including JavaScript or CSS code blocks

### 2. className Alias

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

- [SVG and XML Support](/guide/svg-xml) - Learn about SVG graphics and XML documents
- [Utility Functions](/guide/utils) - Learn about built-in utility functions
- [Components](/guide/components) - Learn how to create and use components
- [TailwindCSS Integration](/guide/tailwindcss) - Learn how to use with TailwindCSS
