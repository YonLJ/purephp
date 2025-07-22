# API Reference

This section provides comprehensive documentation for all PurePHP classes and their methods.

## Core Classes

PurePHP consists of several core classes that work together to provide a powerful templating system:

### [Tag Class](/api/tag)
The base abstract class for all HTML and SVG tags. Provides common functionality for attributes, children, and output methods.

### [HTML Class](/api/html)
Extends Tag class specifically for HTML elements. Includes HTML-specific features like self-closing tag detection and file saving.

### [SVG Class](/api/svg)
Extends XML class for creating SVG graphics. Automatically handles SVG-specific self-closing tags and namespaces.

### [XML Class](/api/xml)
Extends Tag class for creating XML documents. Perfect for configuration files, data export, and API responses.

### [Raw Class](/api/raw)
Represents raw HTML or XML content that bypasses escaping. Useful for including pre-formatted content or templates.

### [DOM Classes](/api/dom)
PDom and NDom classes provide different approaches to DOM representation - PDom for performance, NDom for advanced manipulation.

## Quick Reference

### Creating Elements

```php
<?php

use Pure\Core\{HTML, SVG, XML};
use function Pure\HTML\div;
use function Pure\SVG\circle;

// Function approach (recommended for standard tags)
$element1 = div('Content');

// Magic static method (recommended for custom tags)
$element2 = HTML::customTag('Content');

// Constructor (recommended for performance)
$element3 = new HTML('div', ['Content']);
```

### Common Methods

All Tag-based classes share these common methods:

- `class()` / `className()` - Set CSS classes
- `style()` - Set inline styles
- `id()`, `data_*()`, `aria_*()` - Set attributes
- `getTagName()`, `getAttrs()`, `getChildren()` - Get information
- `toJSON()`, `toPrint()`, `__toString()` - Output methods

### Performance Guidelines

- **Use functions** for standard HTML/SVG tags
- **Use magic methods** for custom or dynamic tags
- **Use constructors** for performance-critical code
- **Use Raw class** for pre-formatted content

## Class Hierarchy

```
Tag (abstract)
├── HTML
├── XML
│   └── SVG
└── Raw

Dom (abstract)
├── PDom
└── NDom
```

## Next Steps

- Browse individual class documentation for detailed examples
- Check out the [Guide](/guide/) for practical usage patterns
- See [SVG and XML Support](/guide/svg-xml) for graphics and data handling
