# SVG and XML Support

PurePHP provides comprehensive support for creating SVG graphics and XML documents with the same elegant syntax as HTML.

*HTML, SVG and XML tag instances all extend `Tag`, so any of them can be wrapped in `Compile::shape()` and rendered with data — see [Compiled Components](/guide/compiled). The SVG sections below are a tag-API reference and render immediately with `render()` / `toPrint()`; the XML sections use the compiled path.*

## SVG Support

### Basic SVG Creation

Create SVG graphics using either magic static methods or constructors:

```php
<?php

use function Pure\SVG\{svg, circle, rect, path};
use Pure\Core\SVG;

// Using function approach (recommended for predefined tags)
$graphic = svg(
    circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->fill('red'),
    rect()
        ->x('10')
        ->y('10')
        ->width('80')
        ->height('80')
        ->fill('blue')
)->width('100')->height('100');

echo $graphic; // Outputs SVG markup
```

### Magic Static Methods vs Constructor

#### Magic Static Methods (Elegant for custom tags)

```php
<?php

use Pure\Core\SVG;

// Clean syntax for any SVG tag
$customElement = SVG::customTag(
    SVG::innerElement('content')
)->customAttribute('value');

// Perfect for non-standard or custom SVG elements
$webComponent = SVG::myCustomSvgElement()
    ->data_id('unique')
    ->class('custom-svg');
```

#### Constructor Method (Performance optimized)

```php
<?php

use Pure\Core\SVG;

// Direct constructor for better performance
$customElement = new SVG('customTag', [
    new SVG('innerElement', ['content'])
])->customAttribute('value');

// Better for performance-critical applications
$webComponent = (new SVG('myCustomSvgElement'))
    ->data_id('unique')
    ->class('custom-svg');
```

### Complex SVG Examples

#### Creating Icons

```php
<?php

use function Pure\SVG\{svg, path, g};

function ChevronIcon($direction = 'right'): SVG
{
    $rotation = match($direction) {
        'up' => 'rotate(-90 12 12)',
        'down' => 'rotate(90 12 12)',
        'left' => 'rotate(180 12 12)',
        default => ''
    };

    return svg(
        path('M9 18l6-6-6-6')
            ->stroke('currentColor')
            ->stroke_width('2')
            ->fill('none')
            ->stroke_linecap('round')
            ->stroke_linejoin('round')
            ->transform($rotation)
    )->width('24')->height('24')->viewBox('0 0 24 24');
}

// Usage
echo ChevronIcon('down')->class('icon');
```

#### Animated SVG

```php
<?php

use Pure\Core\SVG;

$animatedCircle = SVG::svg(
    SVG::circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->fill('red'),
    SVG::animate()
        ->attributeName('r')
        ->values('40;45;40')
        ->dur('2s')
        ->repeatCount('indefinite')
)->width('100')->height('100');
```

## XML Support

XML tags extend `Tag` as well, so a document is built as a compiled shape: the
tree and its slots are created once per process, and each export binds data and
saves or prints it.

### Compiled XML Documents

`AddressShape()` renders one record; `city` is optional and only appears when
the data provides it:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;
use Pure\Core\XML;

function AddressShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        XML::address(
            XML::street(Slot::text('street')),
            Slot::if('city', Compile::shape(XML::city(Slot::text('city')))),
            XML::state(Slot::text('state')),
            XML::zip(Slot::text('zip'))
        )
    );
}

$page = Compile::shape(
    XML::customers(
        XML::customer(
            XML::name('Charter Group'),
            Slot::each('addresses', AddressShape())
        )->id('55000')
    )
);

$data = [
    'addresses' => [
        ['street' => '100 Main', 'city' => 'Framingham', 'state' => 'MA', 'zip' => '01701'],
        ['street' => '720 Prospect', 'city' => 'Framingham', 'state' => 'MA', 'zip' => '01701'],
        ['street' => '120 Ridge', 'state' => 'MA', 'zip' => '01760'],
    ],
];

$page->compile()->save('./example.xml', $data, '<?xml version="1.0"?>');
```

`Slot::each()` renders one `AddressShape()` per record, and `Slot::if()` skips
the `city` element for records without it — a missing key is false and never
throws. The same shape renders to a string with `$page($data)` or
`$page->print($data)`; `Renderer::save()` only prefixes the document header.

### Data-driven Elements

Tag names are fixed at build time, so dynamic keys and values become slots —
here `key` attributes and text content over a list of settings:

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;
use Pure\Core\XML;

$setting = Compile::shape(
    XML::setting(Slot::text('value'))->key(Slot::attr('key'))
);

$config = Compile::shape(
    XML::configuration(Slot::each('settings', $setting))
);

$config->print(['settings' => [
    ['key' => 'host', 'value' => 'localhost'],
    ['key' => 'port', 'value' => '3306'],
    ['key' => 'debug', 'value' => 'true'],
]]);
```

When the structure itself has to vary with the data, use `Slot::if()` or
`Slot::eachAny()`; the tag set of a shape cannot.

## Performance Considerations

### When to Use Magic Methods vs Constructor

**Use Magic Static Methods when:**
- Creating custom or non-standard tags
- Prototyping and development
- Code readability is priority
- Working with dynamic tag names

**Use Constructor when:**
- Performance is critical
- Building libraries or frameworks
- Need maximum type safety
- Working with large documents

### Performance Comparison

```php
<?php

use Pure\Core\HTML;

// Magic method (slightly slower but more elegant)
$element1 = HTML::customTag('content')->customAttr('value');

// Constructor (faster, more explicit)
$element2 = (new HTML('customTag', ['content']))->customAttr('value');

// For predefined tags, use functions (best of both worlds)
use function Pure\HTML\div;
$element3 = div('content')->customAttr('value');
```

## Important: String Content Filtering

⚠️ **Security Note**: String content containing XML/SVG tags is automatically filtered:

```php
<?php

use Pure\Core\XML;
use function Pure\Utils\rawXml;

// ❌ XML tags in strings are stripped
XML::root('<item>This gets filtered</item>')->toPrint();
// Output: <root>This gets filtered</root>

// ✅ Use rawXml to preserve XML content
XML::root(rawXml('<item>This is preserved</item>'))->toPrint();
// Output: <root><item>This is preserved</item></root>
```

**When to use rawXml/rawHtml:**
- Including CDATA sections
- Embedding external XML/SVG content
- Working with pre-formatted markup
- Including complex nested structures

Compiled shapes filter static strings the same way; bound data is escaped by
`Slot::text()` / `Slot::attr()`, and `Slot::raw()` is the verbatim equivalent of
`rawXml()` when data must keep its markup.

## Best Practices

1. **Use functions for predefined HTML/SVG tags** - They provide the best balance of performance and readability
2. **Use magic methods for custom tags** - When you need dynamic tag creation
3. **Use constructors for performance-critical code** - When building libraries or processing large documents
4. **Use rawXml/rawHtml for trusted content** - When you need to preserve markup structure
5. **Combine approaches as needed** - You can mix and match based on your specific use case

## Next Steps

- [API Reference](/api/) - Complete API documentation
- [Components](/guide/components) - Learn about creating reusable components
- [Utility Functions](/guide/utils) - Explore helper functions
