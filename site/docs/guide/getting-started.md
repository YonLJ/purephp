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
)->toPrint();
```

Run the test file:

```bash
php test.php
```

If you see HTML output, the installation was successful.

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

use function Pure\HTML\{div, h1, p};

div(
    h1('My First PurePHP Application'),
    p('Welcome to PurePHP!'),
    p('This is a simple yet powerful PHP template engine.')
)->class('container')->toPrint();
```

### 3. Run the Application

Open `index.php` in a browser or use PHP's built-in server:

```bash
php -S localhost:8000
```

Then visit `http://localhost:8000` to see your first PurePHP application!

## Basic Examples

### Using Components

Create a simple component:

```php
<?php

require 'vendor/autoload.php';

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
    'title' => 'Card Title',
    'content' => 'This is the card content'
])->toPrint();
```

### Setting Attributes

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->class('container')
    ->style('background: #f0f0f0; padding: 20px;')
    ->data_id('main-content')
    ->toPrint();
```

## Next Steps

- [Core Concepts](/guide/concepts) - Understand PurePHP fundamentals
- [Basic Usage](/guide/basic-usage) - Learn basic syntax and usage
- [Components](/guide/components) - Learn how to create and use components
