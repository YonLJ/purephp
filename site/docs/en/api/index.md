# API Reference

This document describes the public methods of PurePHP core classes.

## Tag Class

`Pure\Core\Tag` is the base abstract class for all HTML and SVG tags.

### Attribute Methods

#### `class(string|array|null ...$args): self`

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

#### `className(string|array|null ...$args): self`

Alias for `class()` method, since `class` is a PHP keyword.

```php
<?php

use function Pure\HTML\div;

div('Content')->className('container');
```

#### `style(string|array|null $value): self`

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

### Getter Methods

#### `getTagName(): string`

Gets the tag name.

```php
<?php

use function Pure\HTML\div;

$element = div('Content');
echo $element->getTagName(); // Output: div
```

#### `getAttrs(): array`

Gets all attributes as an associative array.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container')->id('main');
$attrs = $element->getAttrs();
// Returns: ['class' => 'container', 'id' => 'main']
```

#### `getAttr(string $key): string`

Gets the value of a specific attribute.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');
echo $element->getAttr('class'); // Output: container
```

#### `getChildren(): array`

Gets all child elements.

```php
<?php

use function Pure\HTML\{div, p};

$element = div(p('Paragraph 1'), p('Paragraph 2'));
$children = $element->getChildren();
```

### Self-Closing Tag Methods

#### `getSelfClose(): bool`

Checks if the element is a self-closing tag.

```php
<?php

use function Pure\HTML\{div, img};

$div = div('Content');
echo $div->getSelfClose(); // Output: false

$img = img()->src('image.jpg');
echo $img->getSelfClose(); // Output: true
```

#### `setSelfClose(bool $value): self`

Sets whether the element is a self-closing tag.

```php
<?php

use function Pure\HTML\div;

$element = div()->setSelfClose(true);
```

### Output Methods

#### `toJSON(): array`

Converts the element to JSON array format.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');
$json = $element->toJSON();
// Returns: ['tagName' => 'div', 'children' => ['Content'], 'class' => 'container']
```

#### `toPDom(): PDom`

Converts the element to PDom object (for string output).

```php
<?php

use function Pure\HTML\div;

$element = div('Content');
$pdom = $element->toPDom();
echo $pdom; // Output: <div>Content</div>
```

#### `toNDom(): NDom`

Converts the element to NDom object (based on DOMDocument).

```php
<?php

use function Pure\HTML\div;

$element = div('Content');
$ndom = $element->toNDom();
```

#### `toPrint(): void`

Directly outputs the element's HTML string.

```php
<?php

use function Pure\HTML\div;

div('Content')->class('container')->toPrint();
// Output: <div class="container">Content</div>
```

### Dynamic Attribute Methods

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

## HTML Class

`Pure\Core\HTML` extends the Tag class, specifically for HTML tags.

### Static Methods

#### `__callStatic(string $tag, array $children): HTML`

Creates HTML elements through static calls.

```php
<?php

use Pure\Core\HTML;

// Use HTML class directly
$div = HTML::div('Content');
$p = HTML::p('Paragraph');
```

### Save Methods

#### `toSave(string $path, string $header = '<!DOCTYPE html>'): int|false`

Saves the HTML element to a file.

```php
<?php

use function Pure\HTML\{html, head, title, body, div};

$page = html(
    head(title('Page Title')),
    body(div('Page Content'))
);

$result = $page->toSave('output.html');
if ($result !== false) {
    echo "File saved successfully, wrote {$result} bytes";
}
```

### Self-Closing Tags

The HTML class automatically recognizes the following self-closing tags:
- `area`, `base`, `br`, `col`, `embed`, `hr`, `img`, `input`, `link`, `meta`, `source`, `track`, `wbr`

```php
<?php

use function Pure\HTML\{img, br, hr};

// These tags are automatically set as self-closing
img()->src('image.jpg')->alt('Image');
br();
hr();
```

## SVG Class

`Pure\Core\SVG` extends the XML class, specifically for SVG tags.

### Static Methods

#### `__callStatic(string $tag, array $children): SVG`

Creates SVG elements through static calls.

```php
<?php

use Pure\Core\SVG;

// Use SVG class directly
$circle = SVG::circle();
$rect = SVG::rect();
```

### Self-Closing Tags

The SVG class automatically recognizes the following self-closing tags:
- `animate`, `animateMotion`, `circle`, `ellipse`, `feBlend`, `feColorMatrix`, `feDisplacementMap`, `feDropShadow`, `feGaussianBlur`, `feImage`, `image`, `line`, `mpath`, `path`, `polygon`, `polyline`, `rect`, `stop`, `use`

```php
<?php

use function Pure\SVG\{svg, circle, rect};

$graphic = svg(
    circle()->cx('50')->cy('50')->r('40')->fill('red'),
    rect()->x('10')->y('10')->width('80')->height('80')->fill('blue')
)->width('100')->height('100');
```

## XML Class

`Pure\Core\XML` extends the Tag class for creating XML elements.

### Static Methods

#### `__callStatic(string $tag, array $children): XML`

Creates XML elements through static calls.

```php
<?php

use Pure\Core\XML;

// Create XML elements
$customer = XML::customer(
    XML::name('Customer Name'),
    XML::address(
        XML::street('Street Address'),
        XML::city('City'),
        XML::zip('Zip Code')
    )
)->id('123');
```

### Save Methods

#### `toSave(string $path, string $header = '<?xml version="1.0"?>'): int|false`

Saves the XML element to a file.

```php
<?php

use Pure\Core\XML;

$xml = XML::root(
    XML::item('Content 1'),
    XML::item('Content 2')
);

$result = $xml->toSave('output.xml');
if ($result !== false) {
    echo "XML file saved successfully";
}
```

## Raw Class

`Pure\Core\Raw` represents raw HTML or XML content that will not be escaped.

### Constructor

#### `__construct(RawType $type, string $content)`

Creates a Raw object.

```php
<?php

use Pure\Core\Raw;
use Pure\Core\RawType;

// Create raw HTML content
$rawHtml = new Raw(RawType::HTML, '<strong>Bold text</strong>');

// Create raw XML content
$rawXml = new Raw(RawType::XML, '<item>Content</item>');
```

### Output Methods

#### `__toString(): string`

Converts the Raw object to a string.

```php
<?php

use function Pure\Utils\rawHtml;

$raw = rawHtml('<em>Italic text</em>');
echo $raw; // Output: <em>Italic text</em>
```

#### `toJSON(): array`

Converts the Raw object to JSON array format.

```php
<?php

use function Pure\Utils\rawHtml;

$raw = rawHtml('<span>Content</span>');
$json = $raw->toJSON();
// Returns: ['type' => 'HTML', 'content' => '<span>Content</span>']
```

### Utility Functions

It's recommended to use utility functions to create Raw objects:

```php
<?php

use function Pure\Utils\{rawHtml, rawXml};
use function Pure\HTML\div;

// Using rawHtml function
div(
    rawHtml('<strong>This is bold</strong>'),
    rawHtml('<em>This is italic</em>')
)->toPrint();

// Using rawXml function
$xmlContent = rawXml('<item id="1">Content</item>');
```

## PDom Class

`Pure\Core\PDom` is a DOM representation class for string output, extending the Dom abstract class.

### Output Methods

#### `__toString(): string`

Converts the PDom object to HTML/XML string.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');
$pdom = $element->toPDom();
echo $pdom; // Output: <div class="container">Content</div>
```

## NDom Class

`Pure\Core\NDom` is a DOM representation class based on DOMDocument, extending the Dom abstract class.

### Output Methods

#### `__toString(): string`

Converts the NDom object to HTML/XML string.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');
$ndom = $element->toNDom();
echo $ndom; // Output: <div class="container">Content</div>
```

#### `toDom(): DOMElement`

Gets the underlying DOMElement object.

```php
<?php

use function Pure\HTML\div;

$element = div('Content');
$ndom = $element->toNDom();
$domElement = $ndom->toDom(); // Returns DOMElement object
```
