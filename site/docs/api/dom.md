# DOM Classes

PurePHP provides two DOM representation classes for different use cases.

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

### Use Cases

PDom is optimized for string output and is the default choice for most scenarios:

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

// Convert to PDom for string output
$pdom = $page->toPDom();
echo $pdom; // Outputs complete HTML
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

### Use Cases

NDom is useful when you need to interact with PHP's DOMDocument:

```php
<?php

use function Pure\HTML\{div, p};

$element = div(
    p('First paragraph'),
    p('Second paragraph')
);

$ndom = $element->toNDom();
$domElement = $ndom->toDom();

// Use DOMDocument methods
$document = $domElement->ownerDocument;
$xpath = new DOMXPath($document);
$paragraphs = $xpath->query('//p');

foreach ($paragraphs as $p) {
    echo $p->textContent . "\n";
}
```

## Choosing Between PDom and NDom

### Use PDom when:
- You need simple string output
- Performance is important
- You're generating HTML/XML for web responses
- You don't need DOM manipulation

### Use NDom when:
- You need to manipulate the DOM after creation
- You want to use XPath queries
- You need to integrate with existing DOMDocument code
- You need advanced DOM features

## Examples

### Performance Comparison

```php
<?php

use function Pure\HTML\div;

$element = div('Content')->class('container');

// PDom - faster for simple output
$pdom = $element->toPDom();
$html1 = (string)$pdom;

// NDom - more features but slower
$ndom = $element->toNDom();
$html2 = (string)$ndom;

// Both produce the same output
assert($html1 === $html2);
```

### DOM Manipulation with NDom

```php
<?php

use function Pure\HTML\{div, p};

$container = div(
    p('Original content')
)->class('container');

$ndom = $container->toNDom();
$domElement = $ndom->toDom();
$document = $domElement->ownerDocument;

// Add a new paragraph using DOMDocument
$newP = $document->createElement('p', 'Added via DOM');
$domElement->appendChild($newP);

echo $ndom; // Outputs container with both paragraphs
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

$ndom = $content->toNDom();
$domElement = $ndom->toDom();
$document = $domElement->ownerDocument;

// Use XPath to find specific elements
$xpath = new DOMXPath($document);

// Find all paragraphs
$paragraphs = $xpath->query('//p');
echo "Found {$paragraphs->length} paragraphs\n";

// Find spans inside paragraphs
$spans = $xpath->query('//p/span');
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

// Convert to NDom for DOM manipulation
$ndom = $table->toNDom();
$domTable = $ndom->toDom();
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

echo $ndom; // Outputs modified table
```
