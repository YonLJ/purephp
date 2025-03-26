# Core API

This guide documents the core API of PurePHP.

## Virtual DOM Functions

### createElement
```php
<?php

use function Pure\createElement;

// Create a virtual DOM element
$element = createElement('div', [
    'class' => 'container',
    'children' => [
        createElement('h1', ['children' => ['Hello World']]),
        createElement('p', ['children' => ['This is a paragraph.']])
    ]
]);

// Convert to HTML
echo $element->toHTML();
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

// Use the component
render($Greeting(['name' => 'John']));
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

// Use the component
$counter = new Counter();
render($counter->render());
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

// Use the component
render($Counter());
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

// Use the component
render($DataFetcher());
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

// Use the component
render($Form());
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

// Use the memoized component
render($ExpensiveComponent());
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

// Use the component with ref
$ref = createRef();
render($Input(['type' => 'text'], $ref));
```

## Next Steps

- [HTML Tags](/en/api/html-tags) - Learn about HTML tag functions
- [SVG Tags](/en/api/svg-tags) - Understand SVG tag functions
- [Components](/en/guide/components) - Learn about component development
