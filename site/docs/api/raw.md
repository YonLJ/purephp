# Raw Class

`Pure\Core\Raw` represents trusted markup that is emitted verbatim instead of
being escaped.

## Why Raw Content is Important

String content is always escaped, so markup-looking text is displayed instead of
being parsed:

```php
<?php

use function Pure\HTML\div;

// String content is escaped
div('<p>Hello <strong>World</strong></p>')->print();
// Output: <div>&lt;p&gt;Hello &lt;strong&gt;World&lt;/strong&gt;&lt;/p&gt;</div>
```

The Raw class emits content verbatim when you need trusted markup:

```php
<?php

use Pure\Core\Raw;
use function Pure\HTML\div;

// Raw content preserves markup
div(Raw::of('<p>Hello <strong>World</strong></p>'))->print();
// Output: <div><p>Hello <strong>World</strong></p></div>
```

## Creation

### `Raw::of(string $value): self`

Wraps trusted markup in a Raw object. The constructor is private, so this
factory is the only way to create one; the value is a public readonly property:

```php
<?php

use Pure\Core\Raw;

$raw = Raw::of('<strong>Bold text</strong>');

echo $raw->value; // <strong>Bold text</strong>
```

## Output Methods

### `__toString(): string`

Converts the Raw object to a string:

```php
<?php

use Pure\Core\Raw;

$raw = Raw::of('<em>Italic text</em>');
echo $raw; // Output: <em>Italic text</em>
```

## Examples

### Embedding Raw HTML

```php
<?php

use Pure\Core\Raw;
use function Pure\HTML\div;

// Embed pre-formatted HTML content
$content = div(
    Raw::of('<h2>Raw HTML Content</h2>'),
    Raw::of('<p>This content will <strong>not</strong> be escaped.</p>'),
    Raw::of('<script>console.log("JavaScript works!");</script>')
)->class('raw-content');

echo $content;
```

### Including External Content

```php
<?php

use Pure\Core\Raw;
use function Pure\HTML\{div, h1};

// Include content from external source
$externalHtml = file_get_contents('external-content.html');

$page = div(
    h1('My Page'),
    Raw::of($externalHtml)
)->class('page');

echo $page;
```

### Template Includes

```php
<?php

use Pure\Core\Raw;
use function Pure\HTML\{html, head, title, body};

function includeTemplate(string $templatePath): string
{
    ob_start();
    include $templatePath;
    return ob_get_clean();
}

$page = html(
    head(title('My Site')),
    body(
        Raw::of(includeTemplate('header.php')),
        Raw::of(includeTemplate('content.php')),
        Raw::of(includeTemplate('footer.php'))
    )
);

echo $page;
```

### XML with Raw Content

```php
<?php

use Pure\Core\Raw;
use Pure\Core\XML;

$document = XML::document(
    XML::metadata(
        XML::title('Document with Raw Content')
    ),
    XML::content(
        Raw::of('<![CDATA[This is raw XML content with <special> characters]]>')
    )
);

echo $document;
```

### Conditional Raw Content

```php
<?php

use Pure\Core\Raw;
use function Pure\HTML\div;

$isDevelopment = true;

$page = div(
    'Main content here',
    $isDevelopment ? Raw::of('<div class="debug">Debug info</div>') : ''
)->class('page');

echo $page;
```

## Security Considerations

⚠️ **Important**: Raw content is not escaped, so be careful when using user-provided content:

```php
<?php

use Pure\Core\Raw;
use function Pure\HTML\div;

// ❌ DANGEROUS - Never do this with user input
$userInput = $_POST['content']; // Could contain malicious scripts
$dangerous = div(Raw::of($userInput));

// ✅ SAFE - String children are escaped automatically
$userInput = $_POST['content'];
$safe = div($userInput);

// ✅ SAFE - Use Raw only for trusted content
$trustedHtml = '<strong>Admin Message</strong>';
$safe = div(Raw::of($trustedHtml));
```
