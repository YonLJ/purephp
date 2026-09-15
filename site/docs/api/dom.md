# DOM Classes

PurePHP renders to strings directly from the tag tree. For advanced DOM manipulation it provides the Dom representation class, based on DOMDocument.

## String Output

Every tag renders to an HTML string without materializing an intermediate DOM copy:

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');
echo $element; // Output: <div class="container">Content</div>
$html = $element->render();
```

### Use Cases

String output is the default choice for most scenarios:
- You need simple string output
- Performance is important
- You're generating HTML/XML for web responses

```php
<?php

use function Pure\HTML\{html, head, title, body, div, h1, p};

$page = html(
    head(title('My Page')),
    body(
        div(
            h1('Welcome'),
            p('This is my website.')
        )->class('container')
    )
);

echo $page; // Outputs complete HTML
```

## Dom Class

`Pure\Core\Dom` is a DOM representation class based on DOMDocument.

### Output Methods

#### `__toString(): string`

Converts the Dom object to HTML/XML string.

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');
$dom = $element->toDom();
echo $dom; // Output: <div class="container">Content</div>
```

#### `toDom(): DOMElement`

Gets the underlying DOMElement object.

```php
<?php

use function Pure\HTML\div;

$element = div('Content');
$dom = $element->toDom();
$domElement = $dom->toDom(); // Returns DOMElement object
```

### Use Cases

Dom is useful when you need to interact with PHP's DOMDocument:

```php
<?php

use function Pure\HTML\{div, p};

$element = div(
    p('First paragraph'),
    p('Second paragraph')
);

$dom = $element->toDom();
$domElement = $dom->toDom();

// Use DOMDocument methods
$document = $domElement->ownerDocument;
$xpath = new DOMXPath($document);

// Nodes are not attached to the document root, so query relative to the element
$paragraphs = $xpath->query('.//p', $domElement);

foreach ($paragraphs as $p) {
    echo $p->textContent . "\n";
}
```

### Use Dom when:
- You need to manipulate the DOM after creation
- You want to use XPath queries
- You need to integrate with existing DOMDocument code
- You need advanced DOM features

### Differences from String Output

Both paths produce the same markup for plain tags, but they are not byte-for-byte identical:

- Both paths escape text children and attribute values. `render()` skips double-encoding only for text children (so `&copy;` stays `&copy;`), while attribute values and all DOM-serialized text are re-encoded (`&copy;` becomes `&amp;copy;`).
- HTML serialization uses double quotes, switching to single quotes only when a value contains `"`; XML/SVG output (`saveXML`) always uses double quotes and escapes `"` as `&quot;`; `render()` always uses double quotes.
- Void and self-closing elements are serialized in DOM style (`<br>`, `<child/>`), while `render()` writes `<br />`.
- Raw HTML children are parsed and imported into the DOM, so malformed markup is repaired.
- Raw XML that is not well-formed throws instead of being silently dropped.

### Migrating from 1.x

`PDom`, `NDom`, `toPDom()` and `toNDom()` were removed:

- `(string)$element` and `$element->render()` replace `(string)$element->toPDom()`.
- `$element->toDom()` returns the `Dom` wrapper and replaces `$element->toNDom()`.

## Examples

### DOM Manipulation

```php
<?php

use function Pure\HTML\{div, p};

$container = div(
    p('Original content')
)->class('container');

$dom = $container->toDom();
$domElement = $dom->toDom();
$document = $domElement->ownerDocument;

// Add a new paragraph using DOMDocument
$newP = $document->createElement('p', 'Added via DOM');
$domElement->appendChild($newP);

echo $dom; // Outputs container with both paragraphs
```

### XPath Queries

```php
<?php

use function Pure\HTML\{div, p, span};

$content = div(
    p('First paragraph'),
    p(span('Highlighted text'), ' in second paragraph'),
    p('Third paragraph')
)->class('content');

$dom = $content->toDom();
$domElement = $dom->toDom();
$document = $domElement->ownerDocument;

// Use XPath to find specific elements
$xpath = new DOMXPath($document);

// Find all paragraphs
$paragraphs = $xpath->query('.//p', $domElement);
echo "Found {$paragraphs->length} paragraphs\n";

// Find spans inside paragraphs
$spans = $xpath->query('.//p/span', $domElement);
foreach ($spans as $span) {
    echo "Span content: {$span->textContent}\n";
}
```

### Integration with Existing DOM Code

```php
<?php

use function Pure\HTML\{table, tr, td};

// Create table with PurePHP
$table = table(
    tr(td('Cell 1'), td('Cell 2')),
    tr(td('Cell 3'), td('Cell 4'))
)->class('data-table');

// Convert to Dom for DOM manipulation
$dom = $table->toDom();
$domTable = $dom->toDom();
$document = $domTable->ownerDocument;

// Add attributes using DOM methods
$domTable->setAttribute('border', '1');
$domTable->setAttribute('cellpadding', '5');

// Add a new row
$newRow = $document->createElement('tr');
$cell1 = $document->createElement('td', 'Cell 5');
$cell2 = $document->createElement('td', 'Cell 6');
$newRow->appendChild($cell1);
$newRow->appendChild($cell2);
$domTable->appendChild($newRow);

echo $dom; // Outputs modified table
```
