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

The counter text is a bound slot; the endpoint renders the same `CounterValue()`
component with the new count. The templates are shape files:

```php
<?php

// Count.shape.php
return Compile::shape(
    p('Current count: ', Slot::text('count'))->id('counter')
);

// Counter.shape.php
return Compile::shape(
    div(
        Slot::raw('counter'),
        button('Increment')
            ->hxPost('/increment')
            ->hxTarget('#counter')
            ->hxSwap('innerHTML')
    )->class('counter')
);
```

```php
<?php

use Pure\Core\Raw;

use function Pure\HTML\{button, div, p};
use function Pure\Component\render;

function CounterValue(int $count): Raw
{
    return render(__DIR__ . '/Count.shape.php', count: $count);
}

function Counter(int $count): Raw
{
    return render(__DIR__ . '/Counter.shape.php', counter: (string) CounterValue($count));
}

// Render the page
echo Counter(0);

// Handle HTMX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/increment') {
    $count = (int)($_COOKIE['count'] ?? 0) + 1;
    setcookie('count', $count);

    echo CounterValue($count);
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

The result list is a component; every result title is bound with `Slot::text()`,
and the endpoint renders the list component with the search results:

```php
<?php

// SearchResult.shape.php
return Compile::shape(
    div(Slot::text('title'))->class('search-result')
);

// ResultList.shape.php
return Compile::shape(div(Slot::raw('results')));

// SearchBox.shape.php
return Compile::shape(
    div(
        input()
            ->type('text')
            ->placeholder('Search...')
            ->hxGet('/search')
            ->hxTrigger('keyup changed delay:500ms')
            ->hxTarget('#results'),
        div(Slot::raw('list'))->id('results')
    )->class('search-box')
);
```

```php
<?php

use Pure\Core\Raw;

use function Pure\HTML\{div, input};
use function Pure\Component\render;

function SearchResult(string $title): Raw
{
    return render(__DIR__ . '/SearchResult.shape.php', title: $title);
}

function ResultList(array $results): Raw
{
    $items = [];

    foreach ($results as $result) {
        $items[] = (string) SearchResult($result['title']);
    }

    return render(__DIR__ . '/ResultList.shape.php', results: implode('', $items));
}

function SearchBox(Raw $list): Raw
{
    return render(__DIR__ . '/SearchBox.shape.php', list: $list);
}

// Render the page with an empty result list
echo SearchBox(ResultList([]));

// Handle HTMX request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/search') {
    $query = $_GET['q'] ?? '';
    $results = searchItems($query); // Search items

    echo ResultList($results);
    exit;
}
```



## Next Steps

- [HTMX Documentation](https://htmx.org/docs/)
- [PurePHP Components Guide](/guide/components)
- [PurePHP Events Guide](/guide/events)
