# SVG and XML Support

PurePHP provides comprehensive support for creating SVG graphics and XML documents with the same elegant syntax as HTML.

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

### Basic XML Creation

Create XML documents with structured data:

```php
<?php

use Pure\Core\XML;

// Using magic static methods
$document = XML::root(
    XML::metadata(
        XML::title('Document Title'),
        XML::author('Author Name'),
        XML::created('2024-01-01')
    ),
    XML::content(
        XML::section(
            XML::heading('Section 1'),
            XML::paragraph('This is the content of section 1.')
        )->id('section-1')
    )
);

echo $document;
```

### Constructor Approach for XML

```php
<?php

use Pure\Core\XML;

// Using constructor for better performance
$document = new XML('root', [
    new XML('metadata', [
        new XML('title', ['Document Title']),
        new XML('author', ['Author Name']),
        new XML('created', ['2024-01-01'])
    ]),
    new XML('content', [
        (new XML('section', [
            new XML('heading', ['Section 1']),
            new XML('paragraph', ['This is the content of section 1.'])
        ]))->id('section-1')
    ])
]);
```

### XML Configuration Files

```php
<?php

use Pure\Core\XML;

function createConfig(array $settings): XML
{
    $config = XML::configuration();

    foreach ($settings as $key => $value) {
        if (is_array($value)) {
            $section = XML::section()->name($key);
            foreach ($value as $subKey => $subValue) {
                $section = $section->appendChild(
                    XML::setting($subValue)->key($subKey)
                );
            }
            $config = $config->appendChild($section);
        } else {
            $config = $config->appendChild(
                XML::setting($value)->key($key)
            );
        }
    }

    return $config;
}

// Usage
$settings = [
    'database' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'myapp'
    ],
    'debug' => 'true'
];

$configXml = createConfig($settings);
$configXml->toSave('config.xml');
```

### Data Export to XML

```php
<?php

use Pure\Core\XML;

function exportUsersToXml(array $users): XML
{
    $usersXml = XML::users();

    foreach ($users as $userData) {
        $user = XML::user(
            XML::name($userData['name']),
            XML::email($userData['email']),
            XML::role($userData['role'])
        )->id($userData['id']);

        if (!empty($userData['addresses'])) {
            $addresses = XML::addresses();
            foreach ($userData['addresses'] as $addr) {
                $addresses = $addresses->appendChild(
                    XML::address(
                        XML::street($addr['street']),
                        XML::city($addr['city']),
                        XML::zip($addr['zip'])
                    )->type($addr['type'])
                );
            }
            $user = $user->appendChild($addresses);
        }

        $usersXml = $usersXml->appendChild($user);
    }

    return $usersXml;
}
```

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
