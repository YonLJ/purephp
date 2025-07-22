# Core Concepts

This guide explains the core concepts of PurePHP.

## Object-to-HTML Conversion

PurePHP's core is a PHP object-to-HTML string conversion system. It uses PHP objects to represent HTML elements, then converts these objects to HTML strings.

```php
<?php

use function Pure\HTML\{div, p};

// This creates an HTML object
$element = div(
    p('Hello World')
)->class('container');

// Convert to HTML string
echo $element; // or $element->toPrint();
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



## Next Steps

- [Components](/guide/components) - Learn more about component development
- [Props](/guide/props) - Understand props system
- [Events](/guide/events) - Learn about event handling
