# Props

This guide explains the props system in PurePHP.

## Basic Props

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

## HTML Attributes

Props can be used to set HTML attributes.

```php
<?php

use function Pure\HTML\{div, input};

function Input($props) {
    [
        'type' => $type = 'text',
        'name' => $name,
        'value' => $value = '',
        'placeholder' => $placeholder = ''
    ] = $props;

    return input()
        ->type($type)
        ->name($name)
        ->value($value)
        ->placeholder($placeholder)
        ->class('form-input');
}

// Use the component with HTML attributes
Input([
    'type' => 'email',
    'name' => 'email',
    'value' => 'user@example.com',
    'placeholder' => 'Enter your email'
])->toPrint();
```

## Data Attributes

Props can be used to set data attributes.

```php
<?php

use function Pure\HTML\{div};

function DataBox($props) {
    [
        'id' => $id,
        'type' => $type,
        'value' => $value
    ] = $props;

    return div()
        ->data('id', $id)
        ->data('type', $type)
        ->data('value', $value)
        ->class('data-box');
}

// Use the component with data attributes
DataBox([
    'id' => 'box1',
    'type' => 'user',
    'value' => '123'
])->toPrint();
```

## ARIA Attributes

Props can be used to set ARIA attributes for accessibility.

```php
<?php

use function Pure\HTML\{button};

function AccessibleButton($props) {
    [
        'text' => $text,
        'onClick' => $onClick,
        'ariaLabel' => $ariaLabel,
        'ariaExpanded' => $ariaExpanded = false
    ] = $props;

    return button($text)
        ->onclick($onClick)
        ->aria('label', $ariaLabel)
        ->aria('expanded', $ariaExpanded)
        ->class('accessible-btn');
}

// Use the component with ARIA attributes
AccessibleButton([
    'text' => 'Toggle Menu',
    'onClick' => 'toggleMenu()',
    'ariaLabel' => 'Toggle navigation menu',
    'ariaExpanded' => false
])->toPrint();
```

## Chained Attributes

PurePHP supports chained attribute calls.

```php
<?php

use function Pure\HTML\{div};

function StyledBox($props) {
    [
        'width' => $width,
        'height' => $height,
        'color' => $color
    ] = $props;

    return div()
        ->style('width', "{$width}px")
        ->style('height', "{$height}px")
        ->style('background-color', $color)
        ->class('styled-box')
        ->data('width', $width)
        ->data('height', $height)
        ->data('color', $color);
}

// Use the component with chained attributes
StyledBox([
    'width' => 100,
    'height' => 100,
    'color' => '#ff0000'
])->toPrint();
```

## Dynamic Attributes

Props can be used to dynamically set attributes.

```php
<?php

use function Pure\HTML\{div};

function DynamicBox($props) {
    [
        'isActive' => $isActive,
        'isDisabled' => $isDisabled,
        'size' => $size
    ] = $props;

    return div()
        ->class('box')
        ->class('active', $isActive)
        ->class('disabled', $isDisabled)
        ->style('width', "{$size}px")
        ->style('height', "{$size}px");
}

// Use the component with dynamic attributes
DynamicBox([
    'isActive' => true,
    'isDisabled' => false,
    'size' => 200
])->toPrint();
```

## Attribute Validation

Props can be validated before being used as attributes.

```php
<?php

use function Pure\HTML\{div};

function ValidatedBox($props) {
    [
        'width' => $width,
        'height' => $height,
        'color' => $color
    ] = $props;

    // Validate props
    if (!is_numeric($width) || $width <= 0) {
        throw new \InvalidArgumentException('Width must be a positive number');
    }

    if (!is_numeric($height) || $height <= 0) {
        throw new \InvalidArgumentException('Height must be a positive number');
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        throw new \InvalidArgumentException('Color must be a valid hex color');
    }

    return div()
        ->style('width', "{$width}px")
        ->style('height', "{$height}px")
        ->style('background-color', $color)
        ->class('validated-box');
}

// Use the component with validated attributes
ValidatedBox([
    'width' => 100,
    'height' => 100,
    'color' => '#ff0000'
])->toPrint();
```

## Next Steps

- [Components](/guide/components) - Learn more about component development
- [Events](/guide/events) - Understand event handling
