# HTML Tags

This guide documents all available HTML tag functions in PurePHP.

## Document Structure Tags

### html
```php
<?php

use function Pure\HTML\{html, head, body};

html(
    head(
        title('My Page'),
        meta()->charset('UTF-8'),
        link()
            ->rel('stylesheet')
            ->href('style.css')
    ),
    body(
        div('Content')
    )
)->toPrint();
```

### head
```php
<?php

use function Pure\HTML\{head, title, meta, link};

head(
    title('Page Title'),
    meta()->charset('UTF-8'),
    meta()
        ->name('viewport')
        ->content('width=device-width, initial-scale=1.0'),
    link()
        ->rel('stylesheet')
        ->href('style.css')
)->toPrint();
```

### body
```php
<?php

use function Pure\HTML\{body, div};

body(
    div('Page content')
)->toPrint();
```

## Text Content Tags

### Headings
```php
<?php

use function Pure\HTML\{h1, h2, h3, h4, h5, h6};

h1('Main Heading')->toPrint();
h2('Subheading')->toPrint();
h3('Section Heading')->toPrint();
h4('Subsection Heading')->toPrint();
h5('Minor Heading')->toPrint();
h6('Smallest Heading')->toPrint();
```

### Paragraphs and Text
```php
<?php

use function Pure\HTML\{p, span, br, hr};

p('This is a paragraph.')->toPrint();
span('Inline text')->toPrint();
br()->toPrint();
hr()->toPrint();
```

### Lists
```php
<?php

use function Pure\HTML\{ul, ol, li, dl, dt, dd};

// Unordered list
ul(
    li('Item 1'),
    li('Item 2'),
    li('Item 3')
)->toPrint();

// Ordered list
ol(
    li('First item'),
    li('Second item'),
    li('Third item')
)->toPrint();

// Definition list
dl(
    dt('Term'),
    dd('Definition')
)->toPrint();
```

## Form Tags

### Form Elements
```php
<?php

use function Pure\HTML\{form, input, label, button, select, option, textarea};

// Basic form
form(
    label('Username:'),
    input()
        ->type('text')
        ->name('username')
        ->required(),
    label('Password:'),
    input()
        ->type('password')
        ->name('password')
        ->required(),
    button('Submit')
        ->type('submit')
)->toPrint();

// Select element
select(
    option('Option 1')->value('1'),
    option('Option 2')->value('2'),
    option('Option 3')->value('3')
)->name('options')->toPrint();

// Textarea
textarea('Default text')
    ->name('message')
    ->rows(4)
    ->cols(50)
    ->toPrint();
```

### Input Types
```php
<?php

use function Pure\HTML\{input};

// Text input
input()
    ->type('text')
    ->name('username')
    ->placeholder('Enter username')
    ->toPrint();

// Password input
input()
    ->type('password')
    ->name('password')
    ->placeholder('Enter password')
    ->toPrint();

// Email input
input()
    ->type('email')
    ->name('email')
    ->placeholder('Enter email')
    ->toPrint();

// Number input
input()
    ->type('number')
    ->name('age')
    ->min(0)
    ->max(100)
    ->toPrint();

// Date input
input()
    ->type('date')
    ->name('birthday')
    ->toPrint();

// Checkbox
input()
    ->type('checkbox')
    ->name('subscribe')
    ->id('subscribe')
    ->toPrint();

// Radio button
input()
    ->type('radio')
    ->name('gender')
    ->value('male')
    ->id('male')
    ->toPrint();
```

## Table Tags

### Table Structure
```php
<?php

use function Pure\HTML\{table, thead, tbody, tfoot, tr, th, td, caption};

table(
    caption('Table Title'),
    thead(
        tr(
            th('Header 1'),
            th('Header 2'),
            th('Header 3')
        )
    ),
    tbody(
        tr(
            td('Cell 1'),
            td('Cell 2'),
            td('Cell 3')
        )
    ),
    tfoot(
        tr(
            td('Footer 1'),
            td('Footer 2'),
            td('Footer 3')
        )
    )
)->toPrint();
```

## Media Tags

### Images and Media
```php
<?php

use function Pure\HTML\{img, video, audio, source};

// Image
img()
    ->src('image.jpg')
    ->alt('Image description')
    ->width(300)
    ->height(200)
    ->toPrint();

// Video
video(
    source()
        ->src('video.mp4')
        ->type('video/mp4'),
    source()
        ->src('video.webm')
        ->type('video/webm')
)->controls()->width(640)->height(360)->toPrint();

// Audio
audio(
    source()
        ->src('audio.mp3')
        ->type('audio/mpeg')
)->controls()->toPrint();
```

## Interactive Elements

### Links and Buttons
```php
<?php

use function Pure\HTML\{a, button};

// Link
a('Click me')
    ->href('https://example.com')
    ->target('_blank')
    ->toPrint();

// Button
button('Click me')
    ->type('button')
    ->onclick('handleClick()')
    ->toPrint();
```

### Details and Summary
```php
<?php

use function Pure\HTML\{details, summary};

details(
    summary('Click to expand'),
    p('This is the expanded content.')
)->toPrint();
```

## Semantic Elements

### Structure
```php
<?php

use function Pure\HTML\{header, nav, main, article, section, aside, footer};

header(
    nav(
        a('Home')->href('/'),
        a('About')->href('/about'),
        a('Contact')->href('/contact')
    )
)->toPrint();

main(
    article(
        h1('Article Title'),
        p('Article content')
    ),
    aside(
        h2('Sidebar'),
        p('Sidebar content')
    )
)->toPrint();

footer(
    p('Copyright © 2024')
)->toPrint();
```

## Next Steps

- [SVG Tags](/en/api/svg-tags) - Learn about SVG tag functions
- [Core API](/en/api/core) - Understand core API functions
- [Components](/en/guide/components) - Learn about component development
