# PurePHP with TailwindCSS Integration

The combination of PurePHP and TailwindCSS provides a powerful development experience: component-based PHP templating engine paired with a utility-first CSS framework.

*Reusable components in this guide are compiled shapes: static Tailwind class strings are written once at build time and per-request values arrive through slots. See [Compiled Components](/guide/compiled). The immediate tag API (`render()` / `print()`) remains available for snippets, and Tailwind finds the class names in both paths because they always live in PHP source.*

## Why Choose This Combination?

- **PurePHP**: Provides component-based PHP template rendering
- **TailwindCSS**: Provides utility-first CSS class system
- **Perfect Complement**: PurePHP handles structure and logic, TailwindCSS handles styling

## Quick Start

### 1. Install Dependencies

First install PurePHP:

```bash
composer require yonlj/purephp
```

Then install TailwindCSS:

```bash
npm install -D tailwindcss
npx tailwindcss init
```

### 2. Configure TailwindCSS

Configure content paths in `tailwind.config.js`:

```javascript
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.php",
    "./public/**/*.php",
    "./components/**/*.php",
    "./views/**/*.php"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

### 3. Create CSS File

Create `src/input.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### 4. Build CSS

```bash
npx tailwindcss -i ./src/input.css -o ./public/output.css --watch
```

## Basic Usage

### Simple Component

The variant is a static prop, so it is a function argument; the title and
content are dynamic and become slots:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, p};

function CardShape(string $variant = 'default'): Shape
{
    static $shapes = [];

    $baseClasses = 'rounded-lg shadow-md p-6 bg-white';
    $variantClasses = match($variant) {
        'primary' => 'border-l-4 border-blue-500',
        'success' => 'border-l-4 border-green-500',
        'warning' => 'border-l-4 border-yellow-500',
        'danger' => 'border-l-4 border-red-500',
        default => 'border border-gray-200'
    };

    return $shapes[$variant] ??= Compile::shape(
        div(
            h1(Slot::text('title'))->class('text-xl font-bold text-gray-900 mb-2'),
            p(Slot::text('content'))->class('text-gray-600 leading-relaxed')
        )->class("{$baseClasses} {$variantClasses}")
    );
}

// Use component
$bindings = [
    'title' => 'Welcome to PurePHP',
    'content' => 'This is a card component styled with TailwindCSS',
];

CardShape('primary')->print($bindings);
```

### Responsive Layout

The grid has no data of its own; the list binds through `Slot::each()`:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, p, img};

function ProjectCardShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            img()->src(Slot::attr('image'))->alt(Slot::attr('title'))
                ->class('w-full h-48 object-cover rounded-t-lg'),
            div(
                h2(Slot::text('title'))->class('text-lg font-semibold mb-2'),
                p(Slot::text('description'))->class('text-gray-600 text-sm')
            )->class('p-4')
        )->class('bg-white rounded-lg shadow-md overflow-hidden')
    );
}

function ResponsiveGridShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(Slot::each('items', ProjectCardShape()))
            ->class('grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6')
    );
}

// Use responsive grid
$bindings = [
    'items' => [
        ['title' => 'Project 1', 'description' => 'Description 1', 'image' => 'image1.jpg'],
        ['title' => 'Project 2', 'description' => 'Description 2', 'image' => 'image2.jpg'],
        ['title' => 'Project 3', 'description' => 'Description 3', 'image' => 'image3.jpg'],
    ],
];

ResponsiveGridShape()->print($bindings);
```

### Form Components

Static field configuration is passed as arguments; the error message and the
error-state input class are bound per request (`Slot::if()` renders the error
line only when data provides it):

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{form, div, label, input, button, span};

function FormFieldShape(
    string $labelText,
    string $name,
    string $type = 'text',
    string $placeholder = '',
    bool $required = false
): Shape {
    static $shapes = [];

    $key = "{$labelText}|{$name}|{$type}|{$placeholder}|" . (int)$required;

    return $shapes[$key] ??= Compile::shape(
        div(
            label($labelText)
                ->for($name)
                ->class('block text-sm font-medium text-gray-700 mb-1'),
            input()
                ->type($type)
                ->name($name)
                ->id($name)
                ->placeholder($placeholder)
                ->required($required)
                ->class(Slot::attr('inputClass')->default(
                    'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 border-gray-300'
                )),
            Slot::if('error', Compile::shape(
                span(Slot::text('error'))->class('text-red-500 text-sm mt-1')
            ))
        )->class('mb-4')
    );
}

function ContactFormShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        form(
            Slot::child('name', FormFieldShape('Name', 'name', 'text', 'Enter your name', true)),
            Slot::child('email', FormFieldShape('Email', 'email', 'email', 'Enter your email', true)),
            Slot::child('message', FormFieldShape('Message', 'message', 'textarea', 'Enter your message')),
            button('Submit')
                ->type('submit')
                ->class('w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200')
        )->class('max-w-md mx-auto bg-white p-6 rounded-lg shadow-md')
    );
}

// Only the errored field overrides the default input class
$bindings = [
    'name' => [],
    'email' => [
        'error' => 'Enter a valid email address',
        'inputClass' => 'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 border-red-500',
    ],
    'message' => [],
];

ContactFormShape()->print($bindings);
```

## Advanced Usage

### Dynamic Class Names

Variant, size, and state decide the static class list, so they are function
arguments and each combination is memoized as its own shape. Only the label is
dynamic:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\button;

function ButtonShape(
    string $variant = 'primary',
    string $size = 'md',
    bool $disabled = false,
    bool $fullWidth = false
): Shape {
    static $shapes = [];

    $baseClasses = 'font-medium rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

    $variantClasses = match($variant) {
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-blue-500',
        default => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
    };

    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg',
        default => 'px-4 py-2 text-base'
    };

    $widthClasses = $fullWidth ? 'w-full' : '';
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';

    $allClasses = trim("{$baseClasses} {$variantClasses} {$sizeClasses} {$widthClasses} {$disabledClasses}");

    $key = "{$variant}|{$size}|" . (int)$disabled . (int)$fullWidth;

    return $shapes[$key] ??= Compile::shape(
        button(Slot::text('text'))
            ->class($allClasses)
            ->disabled($disabled)
    );
}

// Use dynamic button
$bindings = ['text' => 'Primary Button'];

ButtonShape('primary', 'lg')->print($bindings);
```

### Theme Toggle

The theme decides static class lists, so it is a function argument passed to the
provider and the toggle; the rest of the page arrives through `Slot::child()`:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, main, h1, button};

function PageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        main(h1(Slot::text('title')))->class('container mx-auto p-6')
    );
}

function ThemeToggleShape(string $currentTheme = 'light'): Shape
{
    static $shapes = [];

    $newTheme = $currentTheme === 'light' ? 'dark' : 'light';
    $icon = $currentTheme === 'light' ? '🌙' : '☀️';

    return $shapes[$currentTheme] ??= Compile::shape(
        button("{$icon} Toggle Theme")
            ->onclick("toggleTheme('{$newTheme}')")
            ->class('fixed top-4 right-4 px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600')
    );
}

function ThemeProviderShape(string $theme = 'light'): Shape
{
    static $shapes = [];

    $themeClasses = match($theme) {
        'dark' => 'bg-gray-900 text-white',
        'light' => 'bg-white text-gray-900',
        default => 'bg-white text-gray-900'
    };

    return $shapes[$theme] ??= Compile::shape(
        div(
            Slot::child('toggle', ThemeToggleShape($theme)),
            Slot::child('page', PageShape())
        )->class("min-h-screen {$themeClasses}")
    );
}

// Render the provider for the current theme
$bindings = [
    'toggle' => [],
    'page' => ['title' => 'Dashboard'],
];

ThemeProviderShape('dark')->print($bindings);
```

## Utility Functions

### Class Name Merging Utility

```php
<?php

function clsx(...$classes) {
    $result = [];

    foreach ($classes as $class) {
        if (is_string($class) && !empty(trim($class))) {
            $result[] = trim($class);
        } elseif (is_array($class)) {
            foreach ($class as $key => $value) {
                if (is_numeric($key) && is_string($value)) {
                    $result[] = trim($value);
                } elseif (is_string($key) && $value) {
                    $result[] = trim($key);
                }
            }
        }
    }

    return implode(' ', array_unique(array_filter($result)));
}

// Usage example
$isActive = true;
$hasError = false;

$classes = clsx(
    'base-class',
    'another-class',
    [
        'active' => $isActive,
        'error' => $hasError,
        'text-red-500' => $hasError
    ]
);

echo $classes; // Output: base-class another-class active
```


## Next Steps

- [TailwindCSS Documentation](https://tailwindcss.com/docs)
- [PurePHP Component Guide](/guide/components)
- [PurePHP Utility Functions](/guide/utils)
