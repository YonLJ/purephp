# Raw Class

`Pure\Core\Raw` represents raw HTML or XML content that will not be escaped.

## Why Raw Content is Important

By default, PurePHP automatically filters HTML/XML tags from string content for security reasons:

```php
<?php

use function Pure\HTML\div;

// String content with HTML tags gets filtered
div('<p>Hello <strong>World</strong></p>')->toPrint();
// Output: <div>Hello World</div> (tags are stripped)
```

The Raw class allows you to bypass this filtering when you need to include trusted HTML/XML content:

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// Raw content preserves HTML tags
div(rawHtml('<p>Hello <strong>World</strong></p>'))->toPrint();
// Output: <div><p>Hello <strong>World</strong></p></div>
```

## Constructor

### `__construct(RawType $type, string $content)`

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

## Output Methods

### `__toString(): string`

Converts the Raw object to a string.

```php
<?php

use function Pure\Utils\rawHtml;

$raw = rawHtml('<em>Italic text</em>');
echo $raw; // Output: <em>Italic text</em>
```

### `toJSON(): array`

Converts the Raw object to JSON array format.

```php
<?php

use function Pure\Utils\rawHtml;

$raw = rawHtml('<span>Content</span>');
$json = $raw->toJSON();
// Returns: ['type' => 'HTML', 'content' => '<span>Content</span>']
```

## Utility Functions

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

## Examples

### Embedding Raw HTML

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// Embed pre-formatted HTML content
$content = div(
    rawHtml('<h2>Raw HTML Content</h2>'),
    rawHtml('<p>This content will <strong>not</strong> be escaped.</p>'),
    rawHtml('<script>console.log("JavaScript works!");</script>')
)->class('raw-content');

echo $content;
```

### Including External Content

```php
<?php

use function Pure\HTML\{div, h1};
use function Pure\Utils\rawHtml;

// Include content from external source
$externalHtml = file_get_contents('external-content.html');

$page = div(
    h1('My Page'),
    rawHtml($externalHtml)
)->class('page');

echo $page;
```

### Template Includes

```php
<?php

use function Pure\HTML\{html, head, title, body};
use function Pure\Utils\rawHtml;

function includeTemplate(string $templatePath): string
{
    ob_start();
    include $templatePath;
    return ob_get_clean();
}

$page = html(
    head(title('My Site')),
    body(
        rawHtml(includeTemplate('header.php')),
        rawHtml(includeTemplate('content.php')),
        rawHtml(includeTemplate('footer.php'))
    )
);

echo $page;
```

### XML with Raw Content

```php
<?php

use Pure\Core\XML;
use function Pure\Utils\rawXml;

$document = XML::document(
    XML::metadata(
        XML::title('Document with Raw Content')
    ),
    XML::content(
        rawXml('<![CDATA[This is raw XML content with <special> characters]]>')
    )
);

echo $document;
```

### Conditional Raw Content

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

$isDevelopment = true;

$page = div(
    'Main content here',
    $isDevelopment ? rawHtml('<div class="debug">Debug info</div>') : ''
)->class('page');

echo $page;
```

## Security Considerations

⚠️ **Important**: Raw content is not escaped, so be careful when using user-provided content:

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// ❌ DANGEROUS - Never do this with user input
$userInput = $_POST['content']; // Could contain malicious scripts
$dangerous = div(rawHtml($userInput));

// ✅ SAFE - Sanitize user input first
$userInput = $_POST['content'];
$sanitized = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
$safe = div($sanitized); // This will be automatically escaped

// ✅ SAFE - Use Raw only for trusted content
$trustedHtml = '<strong>Admin Message</strong>';
$safe = div(rawHtml($trustedHtml));
```
