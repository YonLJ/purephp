# Quick Start

This guide will help you get started with PurePHP quickly.

## Create a New Project

1. Create a new directory and initialize Composer:

```bash
mkdir my-purephp-app
cd my-purephp-app
composer init
```

2. Install PurePHP:

```bash
composer require purephp/purephp
```

## Create Entry File

Create `public/index.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function Pure\HTML\{div, h1, p};

// Create a simple layout
div(
    h1('Welcome to PurePHP'),
    p('This is a simple example.')
)->class('container')->toPrint();
```

## Add Styles

Create `public/style.css`:

```css
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

h1 {
    color: #333;
    margin-bottom: 1rem;
}

p {
    color: #666;
    line-height: 1.6;
}
```

Include the stylesheet in your entry file:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function Pure\HTML\{div, h1, p, link};

// Add stylesheet
link()
    ->rel('stylesheet')
    ->href('style.css')
    ->toPrint();

// Create a simple layout
div(
    h1('Welcome to PurePHP'),
    p('This is a simple example.')
)->class('container')->toPrint();
```

## Create Components

1. Create a navigation component:

```php
<?php

use function Pure\HTML\{nav, a};

function Navigation() {
    return nav(
        a('Home')->href('/'),
        a('About')->href('/about'),
        a('Contact')->href('/contact')
    )->class('nav');
}
```

2. Create a card component:

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
```

## Add Interactivity

Create a counter component:

```php
<?php

use function Pure\HTML\{div, button};

function Counter() {
    return div(
        div('Count: 0')->id('count'),
        button('Increment')
            ->onclick('incrementCount()')
    )->class('counter');
}

// Add JavaScript
?>
<script>
let count = 0;

function incrementCount() {
    count++;
    document.getElementById('count').textContent = `Count: ${count}`;
}
</script>
```

## Next Steps

- [Installation](/en/guide/installation) - Learn how to install and configure
- [Core Concepts](/en/guide/concepts) - Understand the core concepts
- [Components](/en/guide/components) - Learn about component development
