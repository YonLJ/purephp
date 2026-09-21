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

### 2. Create the Unit and the Entry File

Create `views/page.cmp.php`, the component unit — the registered template, its
typed props in `prepare()` and the call function:

```php
<?php

// views/page.cmp.php
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{div, h1, p};

function Page(mixed ...$children): Call
{
    return component(__FUNCTION__, ...$children);
}

register(Page(...),
    factory: static fn () =>
        div(
            h1(Slot::value('heading')),
            p(Slot::value('lead')),
            p(Slot::value('body'))
        )->class('container'),
    prepare: static function (string $heading, string $lead, string $body): array {
        return ['heading' => $heading, 'lead' => $lead, 'body' => $body];
    }
);
```

Then `index.php`, the entry file, loads the unit and renders it:

```php
<?php

// index.php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/views/page.cmp.php';

use function Pure\Utils\renderHTML;

echo renderHTML(Page()
    ->heading('My First PurePHP Application')
    ->lead('Welcome to PurePHP!')
    ->body('This is a simple yet powerful PHP template engine.'));
```

A unit lives in its own `*.cmp.php` file — `pure compile` discovers those files,
and the registered name — derived from the call function — resolves to that
file. The binder
loads the template once per process and caches it per name or path;
the document header is the caller's to prepend. Under standard PHP-FPM, enable
`Compile::cachePath()` so requests load the compiled renderer instead of
rebuilding it, or precompile the template with `vendor/bin/pure compile .` so
the binder loads the artifact.

### 3. Run the Application

Open `index.php` in a browser or use PHP's built-in server:

```bash
php -S localhost:8000
```

Then visit `http://localhost:8000` to see your first PurePHP application!

### 4. Enable the Development Guard

While developing, enable the guard so problems that are invisible in the output
are reported instead of silently slowing the page down or rendering as empty:

```php
// index.php, before the first render
Compile::guard(true);           // or set PURE_COMPILE_GUARD=1
```

It emits one `E_USER_WARNING` per subject per process:

- a shape rebuilt per request: the same call site calls `Compile::shape()` 20
  times in one process (the 20th call warns), for example an inline
  `Compile::shape(...)`
  rebuilt on every call. File-backed components resolve through
  `Registry::component()`, which caches the binder per name or path;
- a binding the template never reads, with a `did you mean` suggestion, so a
  misspelled key (`titel`) is not silently ignored;
- an attribute setter whose name is one edit away from a standard attribute
  (`->clas(...)`, `->hreff(...)`), which would otherwise become a custom
  attribute no one notices.

## Basic Examples

### Using Components

A component is a call function returning a `Call`, backed by its own template
and the typed props of its `prepare()` hook:

```php
<?php

// Card.cmp.php

require 'vendor/autoload.php';

use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{div, h2, p};

function Card(mixed ...$children): Call
{
    return component(__FUNCTION__, ...$children);
}

register(Card(...),
    factory: static fn () =>
        div(
            h2(Slot::value('title')),
            p(Slot::value('content'))
        )->class(Slot::value('class')),
    prepare: static function (string $title, string $content, string $class = 'card'): array {
        return ['title' => $title, 'content' => $content, 'class' => $class];
    }
);

// Render the component with data
echo Card()->title('Card Title')->content('This is the card content');
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
