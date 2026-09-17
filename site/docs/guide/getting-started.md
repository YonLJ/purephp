# Quick Start

This guide will help you install PurePHP and create your first application.

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

### Using Composer

Run the following command in your project directory:

```bash
composer require yonlj/purephp
```

### Verify Installation

Create a simple test file `test.php`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use function Pure\HTML\{div, h1, p};

div(
    h1('PurePHP Installation Successful'),
    p('Congratulations! PurePHP is correctly installed.')
)->print();
```

Run the test file:

```bash
php test.php
```

If you see HTML output, the installation was successful. Note that this uses
immediate rendering — it is the right tool for a quick check, but pages should
compile shapes (see below).

## Create Your First Application

### 1. Create Project Directory

```bash
mkdir my-purephp-app
cd my-purephp-app
composer require yonlj/purephp
```

### 2. Create Entry File

Create `index.php`:

```php
<?php

require 'vendor/autoload.php';

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, p};

/**
 * The page shape: a data-free tree with Slot placeholders.
 * It is built once per process (in long-running workers; under standard
 * PHP-FPM enable Compile::cachePath() so requests load the compiled
 * renderer instead of rebuilding it).
 */
function PageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h1(Slot::text('heading')),
            p(Slot::text('lead')),
            p(Slot::text('body'))
        )->class('container')
    );
}

PageShape()->print([
    'heading' => 'My First PurePHP Application',
    'lead' => 'Welcome to PurePHP!',
    'body' => 'This is a simple yet powerful PHP template engine.',
]);
```

### 3. Run the Application

Open `index.php` in a browser or use PHP's built-in server:

```bash
php -S localhost:8000
```

Then visit `http://localhost:8000` to see your first PurePHP application!

### 4. Enable the Development Guard

While developing, enable the guard so a shape that is rebuilt per request is
reported instead of silently slowing the page down:

```php
// index.php, before the first render
Compile::guard(true);           // or set PURE_COMPILE_GUARD=1
```

It emits one `E_USER_WARNING` per call site when the same place calls
`Compile::shape()` too many times in one process, and points at the
`static $shape ??=` pattern.

## Basic Examples

### Using Components

A component is a function that returns a `Shape`; static props are function
arguments, dynamic props are slots:

```php
<?php

require 'vendor/autoload.php';

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, p};

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

// Render the component with data
CardShape()->print([
    'title' => 'Card Title',
    'content' => 'This is the card content',
]);
```

### Setting Attributes

Static attributes are set on the shape; dynamic attributes use
`Slot::attr()`:

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;

$shape = Compile::shape(
    div('Content')->class('container')->id(Slot::attr('id'))
);

$shape(['id' => 'main-content']);
```

For snippets — small fragments that are rendered immediately — you can keep
using the tag API and `print()`:

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->class('container')
    ->style('background: #f0f0f0; padding: 20px;')
    ->data_id('main-content')
    ->print();
```

## Next Steps

- [Compiled Components](/guide/compiled) - Components, lists, conditionals and caching
- [Core Concepts](/guide/concepts) - Understand PurePHP fundamentals
- [Props and Slots](/guide/props) - Learn how data is bound to a shape
