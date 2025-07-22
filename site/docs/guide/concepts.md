# Core Concepts

This guide explains the core concepts of PurePHP.

## Object-to-HTML Conversion

PurePHP's core is a PHP object-to-HTML string conversion system. It uses PHP objects to represent HTML elements, then converts these objects to HTML strings.

### How It Works

1. **Create HTML Objects**
   ```php
   <?php

   use function Pure\HTML\{div, h1, p};

   // Create HTML objects
   $element = div(
       h1('Title'),
       p('Content')
   )->class('container');
   ```

2. **Convert to HTML String**
   ```php
   // Output HTML string
   echo $element; // or $element->toPrint();
   ```

3. **Set Attributes**
   ```php
   // Chain method calls to set attributes
   $element->class('new-container')->style('color: red;');
   ```

### Advantages

- **Type Safety**: Leverages PHP's type system for better development experience
- **Component-based**: HTML structures can be encapsulated into reusable PHP functions
- **Easy Testing**: HTML structure and attributes can be easily tested

## Components

Components are the building blocks for user interfaces in PurePHP.

### Function Components

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

// Use the component
Card([
    'title' => 'Title',
    'content' => 'Content'
])->toPrint();
```

## Props

Props are used to configure component behavior and appearance.

### Basic Props

```php
<?php

use function Pure\HTML\{div, p};

div(
    p('Content')
)
->id('main')
->class('container')
->style('background: #fff;')
->toPrint();
```

### Event Props

```php
<?php

use function Pure\HTML\button;

button('Click me')
    ->onclick('handleClick()')
    ->onmouseover('handleHover()')
    ->toPrint();
```

### Data Props

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->data_id('123')      // Corresponds to data-id="123" in HTML
    ->data_type('card')   // Corresponds to data-type="card" in HTML
    ->toPrint();
```

**Important Note**: Since `-` has special meaning in PHP, all attribute names containing hyphens must use underscores `_` instead.

## State Management

PurePHP supports state management through PHP variables and functions.

### Simple State

```php
<?php

use function Pure\HTML\{div, button, p};

function Counter($count = 0) {
    return div(
        p("Count: {$count}"),
        button('Increment')
            ->onclick("increment()")
    );
}

// Use state
$currentCount = 0;
Counter($currentCount)->toPrint();
```

### Global State

```php
<?php

class Store {
    private static $state = [];

    public static function set($key, $value) {
        self::$state[$key] = $value;
    }

    public static function get($key) {
        return self::$state[$key] ?? null;
    }
}

// Use global state
Store::set('user', ['name' => 'John']);
$user = Store::get('user');
```



## Next Steps

- [Basic Usage](/guide/basic-usage) - Learn basic syntax and usage
- [Utility Functions](/guide/utils) - Learn about built-in utility functions
- [Components](/guide/components) - Deep dive into component development
