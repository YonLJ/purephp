# Core Concepts

This guide explains the core concepts of PurePHP.

## Virtual DOM

PurePHP uses a Virtual DOM to optimize rendering performance. The Virtual DOM is a lightweight copy of the actual DOM that can be manipulated efficiently.

```php
<?php

use function Pure\HTML\{div, p};

// This creates a Virtual DOM node
$node = div(
    p('Hello World')
)->class('container');

// Convert to HTML string
echo $node->toHTML();
```

## Components

Components are reusable UI elements that encapsulate markup and logic.

```php
<?php

use function Pure\HTML\{div, h1, p};

function Card($props) {
    [
        'title' => $title,
        'content' => $content
    ] = $props;

    return div(
        h1($title),
        p($content)
    )->class('card');
}

// Use the component
Card([
    'title' => 'My Card',
    'content' => 'This is a card component.'
])->toPrint();
```

## Props

Props are data passed to components to customize their behavior and appearance.

```php
<?php

use function Pure\HTML\{div, button};

function Button($props) {
    [
        'text' => $text,
        'onClick' => $onClick,
        'disabled' => $disabled = false
    ] = $props;

    return button($text)
        ->onclick($onClick)
        ->disabled($disabled)
        ->class('btn');
}

// Use the component with props
Button([
    'text' => 'Click me',
    'onClick' => 'handleClick()',
    'disabled' => false
])->toPrint();
```

## Events

PurePHP supports event handling through props.

```php
<?php

use function Pure\HTML\{div, input};

function SearchInput($props) {
    [
        'onSearch' => $onSearch
    ] = $props;

    return input()
        ->type('text')
        ->placeholder('Search...')
        ->oninput($onSearch)
        ->class('search-input');
}

// Use the component with event handler
SearchInput([
    'onSearch' => 'handleSearch(event)'
])->toPrint();
```

## Lifecycle

Components have a lifecycle that can be managed through methods.

```php
<?php

class MyComponent {
    public function __construct() {
        // Initialize component
    }

    public function render() {
        // Render component
        return div('Hello World')->toHTML();
    }

    public function __destruct() {
        // Cleanup
    }
}
```

## State Management

PurePHP supports component state through properties.

```php
<?php

class Counter {
    private $count = 0;

    public function increment() {
        $this->count++;
        $this->render();
    }

    public function render() {
        return div(
            div("Count: {$this->count}"),
            button('Increment')
                ->onclick('increment()')
        )->class('counter');
    }
}
```

## Next Steps

- [Components](/en/guide/components) - Learn more about component development
- [Props](/en/guide/props) - Understand props system
- [Events](/en/guide/events) - Learn about event handling
