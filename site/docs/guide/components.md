# Components

Components are the building blocks of user interfaces in PurePHP. This guide explains how to create and use components.

## Function Components

PurePHP uses function components to build user interfaces. Function components are simple PHP functions that accept props and return HTML elements:

```php
<?php

use function Pure\HTML\{div, h2, p, img};

function Card($props) {
    [
        'title' => $title,
        'content' => $content,
        'image' => $image = null
    ] = $props;

    return div(
        $image ? img()->src($image)->class('card-img-top') : null,
        div(
            h2($title)->class('card-title'),
            p($content)->class('card-text')
        )->class('card-body')
    )->class('card');
}

// Use the component
Card([
    'title' => 'Card Title',
    'content' => 'Card Content',
    'image' => 'image.jpg'
])->toPrint();
```

## Component Props

### 1. Basic Props

```php
<?php

use function Pure\HTML\div;

function Box($props) {
    [
        'width' => $width = '100%',
        'height' => $height = '100px',
        'color' => $color = '#000'
    ] = $props;

    return div()
        ->style("width: {$width}; height: {$height}; background: {$color};");
}

// Use the component
Box([
    'width' => '200px',
    'height' => '150px',
    'color' => '#ff0000'
])->toPrint();
```

### 2. Event Props

```php
<?php

use function Pure\HTML\button;

function ActionButton($props) {
    [
        'text' => $text,
        'onClick' => $onClick,
        'disabled' => $disabled = false
    ] = $props;

    return button($text)
        ->onclick($onClick)
        ->disabled($disabled)
        ->class('action-button');
}

// Use the component
ActionButton([
    'text' => 'Submit',
    'onClick' => 'handleSubmit()',
    'disabled' => false
])->toPrint();
```

### 3. Child Components

```php
<?php

use function Pure\HTML\{div, h1};

function Layout($props) {
    [
        'header' => $header,
        'content' => $content,
        'footer' => $footer
    ] = $props;

    return div(
        div($header)->class('header'),
        div($content)->class('content'),
        div($footer)->class('footer')
    )->class('layout');
}

// Use the component
Layout([
    'header' => h1('Title'),
    'content' => 'Main content',
    'footer' => 'Footer'
])->toPrint();
```

## Component Communication

### 1. Props Passing

```php
<?php

use function Pure\HTML\{div, button, p};

function ParentComponent() {
    return div(
        ChildComponent([
            'message' => 'Message from parent component',
            'onAction' => 'handleChildAction'
        ])
    );
}

function ChildComponent($props) {
    [
        'message' => $message,
        'onAction' => $onAction
    ] = $props;

    return div(
        p($message),
        button('Trigger Action')
            ->onclick($onAction)
    );
}
```

### 2. Event Handling

```php
<?php

use function Pure\HTML\button;

function EventButton($props) {
    [
        'text' => $text,
        'onClick' => $onClick
    ] = $props;

    return button($text)->onclick($onClick);
}

// Use event component
EventButton([
    'text' => 'Click Me',
    'onClick' => 'alert("Button clicked!")'
])->toPrint();
```

## Component Reusability

### 1. Higher-Order Components

```php
<?php

use function Pure\HTML\div;

function withLoading($Component) {
    return function($props) use ($Component) {
        [
            'loading' => $loading = false,
            'error' => $error = null,
            ...$rest
        ] = $props;

        if ($loading) {
            return div('Loading...')->class('loading');
        }

        if ($error) {
            return div($error)->class('error');
        }

        return $Component($rest);
    };
}

// Use higher-order component
$LoadingCard = withLoading('Card');
$LoadingCard([
    'loading' => true,
    'title' => 'Title',
    'content' => 'Content'
])->toPrint();
```

### 2. Component Composition

```php
<?php

use function Pure\HTML\div;

function Page($props) {
    [
        'header' => $header,
        'sidebar' => $sidebar,
        'content' => $content,
        'footer' => $footer
    ] = $props;

    return div(
        Header($header),
        div(
            Sidebar($sidebar),
            MainContent($content)
        )->class('main-container'),
        Footer($footer)
    )->class('page');
}

// Use composed components
Page([
    'header' => ['title' => 'Page Title'],
    'sidebar' => ['items' => ['Menu Item 1', 'Menu Item 2']],
    'content' => ['title' => 'Main Content'],
    'footer' => ['copyright' => '© 2024']
])->toPrint();
```



## Next Steps

- [Props](/guide/props) - Learn more about props system
- [Events](/guide/events) - Understand event handling
- [TailwindCSS Integration](/guide/tailwindcss) - Style your components
