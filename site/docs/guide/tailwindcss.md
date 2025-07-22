# PurePHP with TailwindCSS Integration

The combination of PurePHP and TailwindCSS provides a powerful development experience: component-based PHP templating engine paired with a utility-first CSS framework.

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

```php
<?php

use function Pure\HTML\{div, h1, p, button};

function Card($props) {
    [
        'title' => $title,
        'content' => $content,
        'variant' => $variant = 'default'
    ] = $props;

    $baseClasses = 'rounded-lg shadow-md p-6 bg-white';
    $variantClasses = match($variant) {
        'primary' => 'border-l-4 border-blue-500',
        'success' => 'border-l-4 border-green-500',
        'warning' => 'border-l-4 border-yellow-500',
        'danger' => 'border-l-4 border-red-500',
        default => 'border border-gray-200'
    };

    return div(
        h1($title)->class('text-xl font-bold text-gray-900 mb-2'),
        p($content)->class('text-gray-600 leading-relaxed')
    )->class("{$baseClasses} {$variantClasses}");
}

// Use component
Card([
    'title' => 'Welcome to PurePHP',
    'content' => 'This is a card component styled with TailwindCSS',
    'variant' => 'primary'
])->toPrint();
```

### Responsive Layout

```php
<?php

use function Pure\HTML\{div, h2, p, img};

function ResponsiveGrid($items) {
    return div(
        ...array_map(function($item) {
            return div(
                img()->src($item['image'])->alt($item['title'])
                    ->class('w-full h-48 object-cover rounded-t-lg'),
                div(
                    h2($item['title'])->class('text-lg font-semibold mb-2'),
                    p($item['description'])->class('text-gray-600 text-sm')
                )->class('p-4')
            )->class('bg-white rounded-lg shadow-md overflow-hidden');
        }, $items)
    )->class('grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6');
}

// Use responsive grid
$items = [
    ['title' => 'Project 1', 'description' => 'Description 1', 'image' => 'image1.jpg'],
    ['title' => 'Project 2', 'description' => 'Description 2', 'image' => 'image2.jpg'],
    ['title' => 'Project 3', 'description' => 'Description 3', 'image' => 'image3.jpg'],
];

ResponsiveGrid($items)->toPrint();
```

### Form Components

```php
<?php

use function Pure\HTML\{form, div, label, input, button, span};

function FormField($props) {
    [
        'label' => $labelText,
        'type' => $type = 'text',
        'name' => $name,
        'placeholder' => $placeholder = '',
        'required' => $required = false,
        'error' => $error = null
    ] = $props;

    return div(
        label($labelText)
            ->for($name)
            ->class('block text-sm font-medium text-gray-700 mb-1'),
        input()
            ->type($type)
            ->name($name)
            ->id($name)
            ->placeholder($placeholder)
            ->required($required)
            ->class(
                'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500' .
                ($error ? ' border-red-500' : ' border-gray-300')
            ),
        $error ? span($error)->class('text-red-500 text-sm mt-1') : null
    )->class('mb-4');
}

function ContactForm() {
    return form(
        FormField([
            'label' => 'Name',
            'name' => 'name',
            'placeholder' => 'Enter your name',
            'required' => true
        ]),
        FormField([
            'label' => 'Email',
            'type' => 'email',
            'name' => 'email',
            'placeholder' => 'Enter your email',
            'required' => true
        ]),
        FormField([
            'label' => 'Message',
            'type' => 'textarea',
            'name' => 'message',
            'placeholder' => 'Enter your message'
        ]),
        button('Submit')
            ->type('submit')
            ->class('w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200')
    )->class('max-w-md mx-auto bg-white p-6 rounded-lg shadow-md');
}

ContactForm()->toPrint();
```

## Advanced Usage

### Dynamic Class Names

```php
<?php

use function Pure\HTML\button;

function Button($props) {
    [
        'text' => $text,
        'variant' => $variant = 'primary',
        'size' => $size = 'md',
        'disabled' => $disabled = false,
        'fullWidth' => $fullWidth = false
    ] = $props;

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

    return button($text)
        ->class($allClasses)
        ->disabled($disabled);
}

// Use dynamic button
Button([
    'text' => 'Primary Button',
    'variant' => 'primary',
    'size' => 'lg'
])->toPrint();
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
