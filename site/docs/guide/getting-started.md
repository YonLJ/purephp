# Quick Start

This guide will help you install PurePHP and create your first application.

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

### Using Composer

Run the following command in your project directory:

```bash
composer require yonld/purephp
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
composer require yonld/purephp
```

### 2. Create Entry File

Create `index.php`, the entry file: a component unit (a registered template plus
the view function) and its output:

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h1, p};

register('Page', __FILE__, static fn () =>
    div(
        h1(Slot::value('heading')),
        p(Slot::value('lead')),
        p(Slot::value('body'))
    )->class('container')
);

function pageView(array $data): string
{
    // The engine emits the tree as written; prepend the document header here.
    return '<!DOCTYPE html>' . render(
        'Page',
        heading: $data['heading'],
        lead: $data['lead'],
        body: $data['body'],
    );
}

echo pageView([
    'heading' => 'My First PurePHP Application',
    'lead' => 'Welcome to PurePHP!',
    'body' => 'This is a simple yet powerful PHP template engine.',
]);
```

`render()` loads the template once per process; the document header is the
caller's to prepend. Under standard PHP-FPM, enable `Compile::cachePath()` so
requests load the compiled renderer instead of rebuilding it, or precompile the
template with `vendor/bin/pure compile .` so the binder loads the artifact.

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
`Compile::shape()` too many times in one process, for example an inline
`Compile::shape(...)` rebuilt on every call. File-backed components go through
`render()`, which caches the binder per template path.

## Basic Examples

### Using Components

A component is a function with typed parameters returning `string`, backed by
its own template:

```php
<?php

// Card.cmp.php

require 'vendor/autoload.php';

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn () =>
    div(
        h2(Slot::value('title')),
        p(Slot::value('content'))
    )->class(Slot::value('class'))
);

function Card(string $title, string $content, string $class = 'card'): string
{
    return render('Card', title: $title, content: $content, class: $class);
}

// Render the component with data
echo Card('Card Title', 'This is the card content');
```

### Setting Attributes

Static attributes are set on the shape; dynamic attributes use
`Slot::value()`:

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;

$shape = Compile::shape(
    div('Content')->class('container')->id(Slot::value('id'))
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
