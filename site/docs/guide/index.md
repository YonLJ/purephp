# What is PurePHP?

PurePHP is a PHP template engine inspired by ReactJS functional components. It uses PHP objects to represent HTML elements, providing a declarative way to build user interfaces, making code more concise, maintainable, and reusable.

## Why Choose PurePHP?

In traditional PHP development, the view layer often requires mixing HTML, PHP code, and other template syntax, which can be confusing for developers. PurePHP solves these problems by:

- **Pure PHP Implementation**: All code is 100% native PHP, no need to learn new template syntax
- **Component-Based Development**: Eliminate repetitive HTML code through PHP function encapsulation
- **HTML-like Syntax**: Uses syntax very similar to HTML, reducing learning curve
- **Object Conversion**: Uses PHP objects to represent HTML elements, then converts to HTML strings

## Core Features

### 1. Declarative Rendering

PurePHP uses a declarative approach to describe UI, making code more readable and maintainable:

```php
<?php

use function Pure\HTML\{div, h1, p};

div(
    h1('Welcome to PurePHP'),
    p('A PHP template engine')
)->class('container')->toPrint();
```

### 2. Component-Based Development

Split your UI into independent, reusable PHP functions:

```php
<?php

use function Pure\HTML\{div, h2, p};

function Card($props) {
    [
        'title' => $title,
        'content' => $content
    ] = $props;

    return div(
        h2($title),
        p($content)
    )->class('card');
}

// Using components
Card([
    'title' => 'Card Title',
    'content' => 'Card Content'
])->toPrint();
```

### 3. Method Chaining for Attributes

Supports method chaining for setting element attributes:

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->class('container')
    ->style('background: #fff;')
    ->data_id('main')
    ->toPrint();
```

## Advantages

1. **Simple and Easy**: API design is simple and intuitive with a gentle learning curve
2. **Type Safe**: Full support for PHP's type system, providing better development experience
3. **Lightweight**: Small core library with no unnecessary dependencies
4. **Component-Based**: Component-based development makes code easier to maintain and reuse

## Next Steps

- [Quick Start](/guide/getting-started) - Learn how to create your first PurePHP application
- [Core Concepts](/guide/concepts) - Understand PurePHP fundamentals
- [Basic Usage](/guide/basic-usage) - Learn basic syntax and usage
