# Components

This guide explains how to create and use components in PurePHP.

## Basic Components

Components are functions that return Virtual DOM nodes.

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



## Component Composition

Components can be composed together to create complex UIs.

```php
<?php

use function Pure\HTML\{div, nav, a};

function Navigation() {
    return nav(
        a('Home')->href('/'),
        a('About')->href('/about'),
        a('Contact')->href('/contact')
    )->class('nav');
}

function Layout($props) {
    [
        'content' => $content
    ] = $props;

    return div(
        Navigation(),
        div($content)->class('main')
    )->class('layout');
}

// Use the composed components
Layout([
    'content' => Card([
        'title' => 'Welcome',
        'content' => 'This is a composed layout.'
    ])
])->toPrint();
```

## Component Props

Props can be validated and transformed.

```php
<?php

use function Pure\HTML\{div, button};

function Button($props) {
    [
        'text' => $text,
        'onClick' => $onClick,
        'disabled' => $disabled = false,
        'type' => $type = 'button'
    ] = $props;

    // Validate props
    if (!is_string($text)) {
        throw new \InvalidArgumentException('Text must be a string');
    }

    if (!is_callable($onClick)) {
        throw new \InvalidArgumentException('onClick must be callable');
    }

    return button($text)
        ->type($type)
        ->onclick($onClick)
        ->disabled($disabled)
        ->class('btn');
}

// Use the component with validated props
Button([
    'text' => 'Click me',
    'onClick' => 'handleClick()',
    'disabled' => false,
    'type' => 'submit'
])->toPrint();
```

## Component Events

Components can handle events and communicate with parent components.

```php
<?php

use function Pure\HTML\{div, input};

function SearchInput($props) {
    [
        'onSearch' => $onSearch,
        'placeholder' => $placeholder = 'Search...'
    ] = $props;

    return div(
        input()
            ->type('text')
            ->placeholder($placeholder)
            ->oninput($onSearch)
            ->class('search-input'),
        button('Search')
            ->onclick('handleSearch()')
            ->class('search-button')
    )->class('search-container');
}

// Use the component with event handlers
SearchInput([
    'onSearch' => 'handleSearch(event)',
    'placeholder' => 'Enter search term...'
])->toPrint();
```



## Next Steps

- [Props](/guide/props) - Learn more about props system
- [Events](/guide/events) - Understand event handling
- [TailwindCSS Integration](/guide/tailwindcss) - Style your components
