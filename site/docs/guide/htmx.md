# PurePHP with HTMX

PurePHP and HTMX form a powerful combination that allows you to build dynamic, responsive user interfaces while keeping your PHP backend clean and simple.

*HTMX fragments are a natural fit for compiled shapes: `hx-*` attributes are ordinary static tag attributes, so they are set once on the shape, and the endpoint renders the same shape with request data. See [Compiled Components](/guide/compiled).*

## Why This Combination?

- **PurePHP**: Provides componentized PHP template rendering
- **HTMX**: Offers dynamic interaction capabilities without JavaScript
- **Perfect Match**: PurePHP handles server-side rendering, HTMX handles client-side interactions

## Quick Start

### 1. Install Dependencies

```bash
composer require yonlj/purephp
```

Then include HTMX in your HTML:

```html
<script src="https://unpkg.com/htmx.org@2.0.4"></script>
```

### 2. Create Dynamic Components

The counter text is a bound slot; the endpoint renders the same `CountShape()`
fragment with the new count:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, button, p};

function CountShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        p('Current count: ', Slot::text('count'))->id('counter')
    );
}

function CounterShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            Slot::sub('counter', CountShape()),
            button('Increment')
                ->hxPost('/increment')
                ->hxTarget('#counter')
                ->hxSwap('innerHTML')
        )->class('counter')
    );
}

// Render the page
$bindings = ['counter' => ['count' => 0]];

CounterShape()->print($bindings);

// Handle HTMX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/increment') {
    $count = (int)($_COOKIE['count'] ?? 0) + 1;
    setcookie('count', $count);

    CountShape()->print(['count' => $count]);
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

The result list is a shape; every result title is bound with `Slot::text()`, and
the endpoint renders the list shape with the search results:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{input, div};

function SearchResultShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(Slot::text('title'))->class('search-result')
    );
}

function ResultListShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(Slot::each('results', SearchResultShape()))
    );
}

function SearchBoxShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            input()
                ->type('text')
                ->placeholder('Search...')
                ->hxGet('/search')
                ->hxTrigger('keyup changed delay:500ms')
                ->hxTarget('#results'),
            div(Slot::sub('list', ResultListShape()))->id('results')
        )->class('search-box')
    );
}

// Render the page with an empty result list
$bindings = ['list' => ['results' => []]];

SearchBoxShape()->print($bindings);

// Handle HTMX request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/search') {
    $query = $_GET['q'] ?? '';
    $results = searchItems($query); // Search items

    ResultListShape()->print(['results' => $results]);
    exit;
}
```



## Next Steps

- [HTMX Documentation](https://htmx.org/docs/)
- [PurePHP Components Guide](/guide/components)
- [PurePHP Events Guide](/guide/events)
