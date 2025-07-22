# Tag Class

`Pure\Core\Tag` is the base abstract class for all HTML and SVG tags.

## Attribute Methods

### `class(string|array|null ...$args): self`

Sets the CSS class names of the element, with built-in `clx` function to handle multiple arguments.

```php
<?php

use function Pure\HTML\div;

// Single class name
div('Content')->class('container');

// Multiple class names
div('Content')->class('btn', 'btn-primary', 'large');

// Conditional class names
$isActive = true;
div('Content')->class('btn', $isActive ? 'active' : null);

// Array format
div('Content')->class(['btn', 'btn-primary']);
```

### `className(string|array|null ...$args): self`

Alias for `class()` method, since `class` is a PHP keyword.

```php
<?php

use function Pure\HTML\div;

div('Content')->className('container');
```

### `style(string|array|null $value): self`

Sets the inline styles of the element, supporting both string and array formats.

```php
<?php

use function Pure\HTML\div;

// String format
div('Content')->style('background: #fff; padding: 20px;');

// Array format (built-in sty function)
div('Content')->style([
    'background-color' => '#fff',
    'padding' => '20px',
    'border-radius' => '8px'
]);
```

## Getter Methods

### `getTagName(): string`

Gets the tag name.

```php
<?php

use function Pure\HTML\div;

$element = div('Content');
echo $element->getTagName(); // Output: div
```

### `getAttrs(): array`

Gets all attributes as an associative array.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container')->id('main');
$attrs = $element->getAttrs();
// Returns: ['class' => 'container', 'id' => 'main']
```

### `getAttr(string $key): string`

Gets the value of a specific attribute.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');
echo $element->getAttr('class'); // Output: container
```

### `getChildren(): array`

Gets all child elements.

```php
<?php

use function Pure\HTML\{div, p};

$element = div(p('Paragraph 1'), p('Paragraph 2'));
$children = $element->getChildren();
```

## Self-Closing Tag Methods

### `getSelfClose(): bool`

Checks if the element is a self-closing tag.

```php
<?php

use function Pure\HTML\{div, img};

$div = div('Content');
echo $div->getSelfClose(); // Output: false

$img = img()->src('image.jpg');
echo $img->getSelfClose(); // Output: true
```

### `setSelfClose(bool $value): self`

Sets whether the element is a self-closing tag.

```php
<?php

use function Pure\HTML\div;

$element = div()->setSelfClose(true);
```

## Output Methods

### `toJSON(): array`

Converts the element to JSON array format.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');
$json = $element->toJSON();
// Returns: ['tagName' => 'div', 'children' => ['Content'], 'class' => 'container']
```

### `toPDom(): PDom`

Converts the element to PDom object (for string output).

```php
<?php

use function Pure\HTML\div;

$element = div('Content');
$pdom = $element->toPDom();
echo $pdom; // Output: <div>Content</div>
```

### `toNDom(): NDom`

Converts the element to NDom object (based on DOMDocument).

```php
<?php

use function Pure\HTML\div;

$element = div('Content');
$ndom = $element->toNDom();
```

### `toPrint(): void`

Directly outputs the element's HTML string.

```php
<?php

use function Pure\HTML\div;

div('Content')->class('container')->toPrint();
// Output: <div class="container">Content</div>
```

## Dynamic Attribute Methods

The Tag class supports dynamically setting any HTML attribute through the `__call` magic method:

```php
<?php

use function Pure\HTML\{div, input, img};

// Set ID
div('Content')->id('main');

// Set data attributes (note the use of underscores)
div('Content')->data_id('123')->data_type('card');

// Set ARIA attributes
div('Content')->aria_label('Main content');

// Set form attributes
input()->type('text')->name('username')->placeholder('Enter username');

// Set image attributes
img()->src('image.jpg')->alt('Image description')->width('100')->height('100');
```
