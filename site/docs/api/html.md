# HTML Class

`Pure\Core\HTML` extends the Tag class, specifically for HTML tags.

## Creating HTML Elements

Standard tags come from the `Pure\HTML` functions. Any other tag name works as a
magic static call whose *method name is the tag name*: `HTML::myWidget('x')`
builds `<myWidget>x</myWidget>`, and a hyphenated custom element goes through the
dynamic form, `HTML::{'user-card'}('x')`.

### 1. Functions (Standard tags)

```php
<?php

use function Pure\HTML\{div, p, span};

$div = div('Content');
$p = p('Paragraph');
$span = span('Text')->class('highlight');
```

Functions follow tag names one to one, with one exception: a tag named after a
PHP keyword cannot name a function, so `<var>` comes from `Pure\HTML\htmlVar()`.
This mirrors SVG's `Pure\SVG\svgUse()` for `<use>` and `Pure\SVG\svgSwitch()`
for `<switch>`; the magic static surface (`HTML::var()`) covers the element too.

### 2. Magic Static Methods (Custom tags)

```php
<?php

use Pure\Core\HTML;

// Works with any tag name: the method name is the tag name
$element = HTML::customTag('Content')->class('custom'); // <customTag class="custom">Content</customTag>
$component = HTML::myWebComponent(HTML::header('Header'));

// A hyphenated custom element goes through the dynamic form
$webComponent = HTML::{'user-card'}('Content'); // <user-card>Content</user-card>
```

**Use cases:**
- Custom HTML tags
- Web components
- Non-standard HTML elements

## Save Methods

### `save(string $path, ?string $header = null): int|false`

Saves the HTML element to a file. When `$header` is omitted, `<!DOCTYPE html>`
is written first.

```php
<?php

use function Pure\HTML\{html, head, title, body, div};

$page = html(
    head(title('Page Title')),
    body(div('Page Content'))
);

$result = $page->save('output.html');
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
        input()->type('text')->id('name')->name('name')->required(true)
    )->class('form-group'),
    div(
        label('Email:')->for('email'),
        input()->type('email')->id('email')->name('email')->required(true)
    )->class('form-group'),
    div(
        label('Message:')->for('message'),
        textarea('')->id('message')->name('message')->rows('5')->required(true)
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

### Large Lists

```php
<?php

use function Pure\HTML\{li, ul};

// Generate large lists efficiently
$items = [];
for ($i = 1; $i <= 1000; $i++) {
    $items[] = li("Item $i");
}

$list = ul($items);
echo $list;
```
