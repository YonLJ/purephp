# HTML Class

`Pure\Core\HTML` extends the Tag class, specifically for HTML tags.

## Creating HTML Elements

PurePHP provides two ways to create HTML elements:

### 1. Magic Static Methods (Recommended for predefined tags)

```php
<?php

use Pure\Core\HTML;

// Use HTML class directly with magic methods
$div = HTML::div('Content');
$p = HTML::p('Paragraph');
$span = HTML::span('Text')->class('highlight');
```

**Advantages:**
- Clean and elegant syntax
- Works with any tag name
- Perfect for custom or non-standard tags

**Use cases:**
- Custom HTML tags
- Web components
- Non-standard HTML elements

### 2. Constructor Method (Recommended for performance-critical code)

```php
<?php

use Pure\Core\HTML;

// Use constructor directly
$div = new HTML('div', ['Content']);
$p = new HTML('p', ['Paragraph']);
$span = (new HTML('span', ['Text']))->class('highlight');

// For tags without children, you can omit the second parameter
$img = new HTML('img');
$br = new HTML('br');
```

**Advantages:**
- Better performance (no magic method overhead)
- Better IDE support and type checking
- More explicit

**Use cases:**
- Performance-critical applications
- When you need maximum type safety
- Library development

## Save Methods

### `toSave(string $path, string $header = '<!DOCTYPE html>'): int|false`

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

## Self-Closing Tags

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

## Examples

### Basic HTML Structure

```php
<?php

use function Pure\HTML\{html, head, meta, title, body, div, h1, p};

$page = html(
    head(
        meta()->charset('UTF-8'),
        meta()->name('viewport')->content('width=device-width, initial-scale=1.0'),
        title('My Page')
    ),
    body(
        div(
            h1('Welcome'),
            p('This is my website.')
        )->class('container')
    )
)->lang('en');

echo $page;
```

### Form Creation

```php
<?php

use function Pure\HTML\{form, div, label, input, textarea, button};

$contactForm = form(
    div(
        label('Name:')->for('name'),
        input()->type('text')->id('name')->name('name')->required()
    )->class('form-group'),
    div(
        label('Email:')->for('email'),
        input()->type('email')->id('email')->name('email')->required()
    )->class('form-group'),
    div(
        label('Message:')->for('message'),
        textarea('')->id('message')->name('message')->rows('5')->required()
    )->class('form-group'),
    button('Send Message')->type('submit')
)->method('POST')->action('/contact');

echo $contactForm;
```

### Custom Components with Magic Methods

```php
<?php

use Pure\Core\HTML;

// Create custom web components
$customCard = HTML::cardComponent(
    HTML::cardHeader('Card Title'),
    HTML::cardBody('Card content goes here'),
    HTML::cardFooter('Card footer')
)->data_component('card')->class('custom-card');

echo $customCard;
```

### Performance-Critical Generation

```php
<?php

use Pure\Core\HTML;

// Generate large lists efficiently
$items = [];
for ($i = 1; $i <= 1000; $i++) {
    $items[] = new HTML('li', ["Item $i"]);
}

$list = new HTML('ul', $items);
echo $list;
```
