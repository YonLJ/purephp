# Props

PurePHP provides a powerful props system for configuring and customizing component behavior and appearance.

## Basic Props

### 1. HTML Attributes

```php
<?php

use function Pure\HTML\div;

// Set basic attributes
div('Content')
    ->id('main')
    ->class('container')
    ->style('background: #fff;')
    ->toPrint();
```

### 2. Data Attributes

```php
<?php

use function Pure\HTML\div;

// Set data attributes
div('Content')
    ->data_id('123')
    ->data_type('card')
    ->data_status('active')
    ->toPrint();
```

### 3. ARIA Attributes

```php
<?php

use function Pure\HTML\button;

// Set ARIA attributes
button('Submit')
    ->aria_label('Submit form')
    ->aria_disabled('false')
    ->aria_required('true')
    ->toPrint();
```

## Chained Attributes

PurePHP supports chained attribute method calls:

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->id('main')
    ->class('container')
    ->style('background: #fff;')
    ->data_type('card')
    ->aria_label('Main content')
    ->toPrint();
```

## Dynamic Props

### 1. Conditional Props

```php
<?php

use function Pure\HTML\div;

function DynamicBox($props) {
    [
        'active' => $active = false,
        'disabled' => $disabled = false
    ] = $props;

    return div('Content')
        ->class('box')
        ->class($active ? 'active' : '')
        ->class($disabled ? 'disabled' : '')
        ->data_active($active)
        ->data_disabled($disabled);
}

// Use the component
DynamicBox([
    'active' => true,
    'disabled' => false
])->toPrint();
```

### 2. Computed Props

```php
<?php

use function Pure\HTML\div;

function ResponsiveBox($props) {
    [
        'width' => $width = 100,
        'height' => $height = 100
    ] = $props;

    $style = sprintf(
        'width: %dpx; height: %dpx; aspect-ratio: %d/%d;',
        $width,
        $height,
        $width,
        $height
    );

    return div('Content')
        ->style($style)
        ->class('responsive-box');
}

// Use the component
ResponsiveBox([
    'width' => 200,
    'height' => 150
])->toPrint();
```

## Props Validation

### 1. Type Checking

```php
<?php

use function Pure\HTML\div;

function ValidatedBox($props) {
    [
        'width' => $width,
        'height' => $height,
        'color' => $color
    ] = $props;

    // Validate prop types
    if (!is_numeric($width) || !is_numeric($height)) {
        throw new \InvalidArgumentException('Width and height must be numbers');
    }

    if (!is_string($color)) {
        throw new \InvalidArgumentException('Color must be a string');
    }

    return div('Content')
        ->style("width: {$width}px; height: {$height}px; background: {$color};");
}

// Use the component
try {
    ValidatedBox([
        'width' => 200,
        'height' => 150,
        'color' => '#ff0000'
    ])->toPrint();
} catch (\InvalidArgumentException $e) {
    echo "Error: {$e->getMessage()}";
}
```

### 2. Required Props

```php
<?php

use function Pure\HTML\div;

function RequiredBox($props) {
    // Check required props
    $required = ['id', 'type'];
    foreach ($required as $prop) {
        if (!isset($props[$prop])) {
            throw new \InvalidArgumentException("{$prop} is a required prop");
        }
    }

    return div('Content')
        ->id($props['id'])
        ->data_type($props['type']);
}

// Use the component
try {
    RequiredBox([
        'id' => 'box1',
        'type' => 'card'
    ])->toPrint();
} catch (\InvalidArgumentException $e) {
    echo "Error: {$e->getMessage()}";
}
```

## Default Props

### 1. Using Default Values

```php
<?php

use function Pure\HTML\div;

function CustomComponent($props) {
    // Set default props
    $defaultProps = [
        'theme' => 'light',
        'size' => 'medium',
        'disabled' => false
    ];

    $props = array_merge($defaultProps, $props);

    [
        'theme' => $theme,
        'size' => $size,
        'disabled' => $disabled,
        'content' => $content
    ] = $props;

    return div($content)
        ->class("custom-component theme-{$theme} size-{$size}")
        ->data_disabled($disabled ? 'true' : 'false');
}

// Use the component
CustomComponent([
    'theme' => 'dark',
    'size' => 'large',
    'content' => 'Custom content'
])->toPrint();
```

### 2. Props Merging

```php
<?php

use function Pure\HTML\div;

function MergedBox($props) {
    [
        'class' => $class = '',
        'style' => $style = '',
        'data' => $data = []
    ] = $props;

    // Merge class names
    $classes = array_merge(
        ['box'],
        explode(' ', $class)
    );

    // Merge styles
    $styles = array_merge(
        ['background: #fff;'],
        explode(';', $style)
    );

    // Merge data attributes
    $dataAttrs = array_merge(
        ['type' => 'box'],
        $data
    );

    return div('Content')
        ->class(implode(' ', array_filter($classes)))
        ->style(implode(';', array_filter($styles)))
        ->data($dataAttrs);
}

// Use the component
MergedBox([
    'class' => 'custom-box',
    'style' => 'color: #000;',
    'data' => ['status' => 'active']
])->toPrint();
```

## Props Transformation

### 1. Type Conversion

```php
<?php

use function Pure\HTML\div;

function TypedBox($props) {
    [
        'width' => $width,
        'height' => $height,
        'opacity' => $opacity
    ] = $props;

    // Convert prop types
    $width = (int) $width;
    $height = (int) $height;
    $opacity = (float) $opacity;

    return div('Content')
        ->style("width: {$width}px; height: {$height}px; opacity: {$opacity};");
}

// Use the component
TypedBox([
    'width' => '200',
    'height' => '150',
    'opacity' => '0.5'
])->toPrint();
```

### 2. Value Transformation

```php
<?php

use function Pure\HTML\div;

function TransformedBox($props) {
    [
        'color' => $color,
        'size' => $size
    ] = $props;

    // Transform color value
    $color = str_starts_with($color, '#') ? $color : "#{$color}";

    // Transform size value
    $size = str_ends_with($size, 'px') ? $size : "{$size}px";

    return div('Content')
        ->style("color: {$color}; font-size: {$size};");
}

// Use the component
TransformedBox([
    'color' => 'ff0000',
    'size' => '16'
])->toPrint();
```

## Next Steps

- [Components](/guide/components) - Learn more about component development
- [Events](/guide/events) - Understand event handling
