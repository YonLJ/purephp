# API Reference

This section contains the complete API reference for PurePHP.

## Core API

The core API provides the fundamental functionality of PurePHP:

- [Core Classes](/en/api/core) - Essential classes and functions
- [HTML Tags](/en/api/html-tags) - HTML tag functions
- [SVG Tags](/en/api/svg-tags) - SVG tag functions

## Virtual DOM Functions

### createElement
```php
<?php

use function Pure\createElement;

// Create a virtual DOM element
$element = createElement('div', [
    'class' => 'container',
    'children' => ['Hello World']
]);
```

### render
```php
<?php

use function Pure\{render, createElement};

// Render a virtual DOM element
render(
    createElement('div', [
        'class' => 'container',
        'children' => ['Hello World']
    ])
);
```

## Component Functions

### createComponent
```php
<?php

use function Pure\createComponent;

// Create a functional component
$Greeting = createComponent(function($props) {
    [
        'name' => $name = 'World'
    ] = $props;

    return createElement('div', [
        'class' => 'greeting',
        'children' => ["Hello, {$name}!"]
    ]);
});
```

### createClassComponent
```php
<?php

use function Pure\createClassComponent;

// Create a class component
class Counter extends createClassComponent {
    private $count = 0;

    public function increment() {
        $this->count++;
        $this->render();
    }

    public function render() {
        return createElement('div', [
            'class' => 'counter',
            'children' => [
                createElement('p', ['children' => ["Count: {$this->count}"]]),
                createElement('button', [
                    'onclick' => 'increment()',
                    'children' => ['Increment']
                ])
            ]
        ]);
    }
}
```

## State Management

### useState
```php
<?php

use function Pure\{useState, createComponent};

// Create a component with state
$Counter = createComponent(function() {
    [$count, $setCount] = useState(0);

    return createElement('div', [
        'class' => 'counter',
        'children' => [
            createElement('p', ['children' => ["Count: {$count}"]]),
            createElement('button', [
                'onclick' => "setCount({$count} + 1)",
                'children' => ['Increment']
            ])
        ]
    ]);
});
```

### useEffect
```php
<?php

use function Pure\{useEffect, useState, createComponent};

// Create a component with side effects
$DataFetcher = createComponent(function() {
    [$data, $setData] = useState(null);
    [$loading, $setLoading] = useState(true);

    useEffect(function() use ($setData, $setLoading) {
        // Simulate API call
        $response = ['items' => [1, 2, 3]];
        $setData($response);
        $setLoading(false);
    }, []); // Empty dependency array means run once

    if ($loading) {
        return createElement('div', ['children' => ['Loading...']]);
    }

    return createElement('div', [
        'class' => 'data-list',
        'children' => array_map(function($item) {
            return createElement('div', ['children' => ["Item: {$item}"]]);
        }, $data['items'])
    ]);
});
```

## Event Handling

### createEventHandler
```php
<?php

use function Pure\{createEventHandler, createComponent};

// Create a component with event handling
$Form = createComponent(function() {
    $handleSubmit = createEventHandler(function($event) {
        $event->preventDefault();
        // Handle form submission
        echo 'Form submitted!';
    });

    return createElement('form', [
        'onsubmit' => $handleSubmit,
        'children' => [
            createElement('input', [
                'type' => 'text',
                'name' => 'username'
            ]),
            createElement('button', [
                'type' => 'submit',
                'children' => ['Submit']
            ])
        ]
    ]);
});
```

## Utility Functions

### memo
```php
<?php

use function Pure\{memo, createComponent};

// Create a memoized component
$ExpensiveComponent = memo(function($props) {
    // Expensive computation
    sleep(1);
    return createElement('div', [
        'children' => ['Expensive Result']
    ]);
});
```

### forwardRef
```php
<?php

use function Pure\{forwardRef, createComponent};

// Create a component that forwards refs
$Input = forwardRef(function($props, $ref) {
    return createElement('input', array_merge($props, [
        'ref' => $ref
    ]));
});
```

## Next Steps

- [Core Classes](/en/api/core) - Learn about core classes and functions
- [HTML Tags](/en/api/html-tags) - Explore HTML tag functions
- [SVG Tags](/en/api/svg-tags) - Understand SVG tag functions
