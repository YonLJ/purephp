# What is PurePHP?

PurePHP is a Virtual DOM-based PHP template engine inspired by ReactJS. It provides a declarative way to build user interfaces, making code more concise, maintainable, and reusable.

## Features

### 1. Declarative Rendering

With PurePHP, you can describe your UI in a declarative way:

```php
<?php

use function Pure\HTML\{div, h1, p};

div(
    h1('Welcome to PurePHP'),
    p('A Virtual DOM-based PHP template engine')
)->class('container')->toPrint();
```

### 2. Component-Based Development

Split your UI into independent, reusable components:

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

// Using components
div(
    Card([
        'title' => 'Title 1',
        'content' => 'Content 1'
    ]),
    Card([
        'title' => 'Title 2',
        'content' => 'Content 2'
    ])
)->class('card-grid')->toPrint();
```

### 3. Virtual DOM

PurePHP uses Virtual DOM to optimize rendering performance:

```php
<?php

use Pure\Core\{HTML, render};

// Create Virtual DOM nodes
$node = new HTML('div', ['class' => 'container'], [
    new HTML('h1', [], ['Title']),
    new HTML('p', [], ['Content'])
]);

// Render to actual DOM
render($node);
```

### 4. Reactive Updates

UI automatically updates when data changes:

```php
<?php

use Pure\Core\{HTML, render};

class TodoList extends HTML {
    private $todos = [];

    public function addTodo($text) {
        $this->todos[] = $text;
        $this->render();
    }

    public function render() {
        return new HTML('ul', ['class' => 'todo-list'],
            array_map(
                fn($todo) => new HTML('li', [], [$todo]),
                $this->todos
            )
        );
    }
}
```

## Core Concepts

Before diving into the details, let's understand the core concepts of PurePHP:

- **Virtual DOM**: A lightweight representation of the actual DOM
- **Components**: Reusable UI building blocks
- **Props**: Data passed to components
- **Events**: User interactions and system events

## Getting Started

To get started with PurePHP:

1. [Installation](/en/guide/installation) - Learn how to install and configure PurePHP
2. [Quick Start](/en/guide/getting-started) - Create your first PurePHP application
3. [Core Concepts](/en/guide/concepts) - Understand the fundamental concepts

## Basic Features

PurePHP provides several key features:

- [Components](/en/guide/components) - Build reusable UI components
- [Props](/en/guide/props) - Handle component data and attributes
- [Events](/en/guide/events) - Manage user interactions and system events

## Next Steps

- [API Reference](/en/api/core) - Explore the complete API documentation
- [Examples](/en/guide/examples) - View example applications
- [Contributing](/en/guide/contributing) - Learn how to contribute to PurePHP
