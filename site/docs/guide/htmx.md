# PurePHP with HTMX

PurePHP and HTMX form a powerful combination that allows you to build dynamic, responsive user interfaces while keeping your PHP backend clean and simple.

## Why This Combination?

- **PurePHP**: Provides componentized PHP template rendering
- **HTMX**: Offers dynamic interaction capabilities without JavaScript
- **Perfect Match**: PurePHP handles server-side rendering, HTMX handles client-side interactions

## Quick Start

### 1. Install Dependencies

```bash
composer require purephp/purephp
```

Then include HTMX in your HTML:

```html
<script src="https://unpkg.com/htmx.org@2.0.4"></script>
```

### 2. Create Dynamic Components

```php
<?php

use function Pure\HTML\{div, button, p};

function Counter() {
    return div(
        p('Current count: 0')->id('counter'),
        button('Increment')
            ->hxPost('/increment')
            ->hxTarget('#counter')
            ->hxSwap('innerHTML')
    )->class('counter');
}

// Handle HTMX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/increment') {
    $count = (int)($_COOKIE['count'] ?? 0) + 1;
    setcookie('count', $count);
    echo "Current count: {$count}";
    exit;
}
```

### 3. Infinite Scroll List

```php
<?php

use function Pure\HTML\{div, ul, li};

function TodoList() {
    return div(
        ul()->id('todos'),
        button('Load More')
            ->hxGet('/todos?page=1')
            ->hxTarget('#todos')
            ->hxSwap('beforeend')
    )->class('todo-list');
}

// Handle HTMX request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/todos') === 0) {
    $page = (int)($_GET['page'] ?? 1);
    $todos = getTodos($page); // Get todo items

    foreach ($todos as $todo) {
        echo li($todo['title'])->class('todo-item');
    }
    exit;
}
```

### 4. Live Search

```php
<?php

use function Pure\HTML\{input, div};

function SearchBox() {
    return div(
        input()
            ->type('text')
            ->placeholder('Search...')
            ->hxGet('/search')
            ->hxTrigger('keyup changed delay:500ms')
            ->hxTarget('#results'),
        div()->id('results')
    )->class('search-box');
}

// Handle HTMX request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/search') {
    $query = $_GET['q'] ?? '';
    $results = searchItems($query); // Search items

    foreach ($results as $result) {
        echo div($result['title'])->class('search-result');
    }
    exit;
}
```



## Example Project

Check out our [example project](https://github.com/yourusername/purephp-htmx-example) for more use cases and best practices.

## Next Steps

- [HTMX Documentation](https://htmx.org/docs/)
- [PurePHP Components Guide](/guide/components)
- [PurePHP Events Guide](/guide/events)
