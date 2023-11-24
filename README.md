# Tiny

Tiny is a Virtual DOM based templating-engine for PHP inspired by ReactJS.

## What is it

Tiny is a template engine that can output HTML5 strings or files.

+ Tiny mainly consists of 3 parts: `Tag`, `PDom` and `tags`.
+ The `Tag` class is responsible for generating virtual Dom data.
+ The `PDom/TDom` class is responsible for rendering virtual dom data into html string.
+ The `tags` file provides a number of commonly used HTML5 tags, all of which are instances of the Tag class.

It also works on xml.

## Install

`composer require yonlj/tiny`

## Basic usage

Here is a simple example that will show how to use `Tiny`:

```php
<?php

use Tiny\Core\PDom;
use Tiny\Core\Tag;
use function Tiny\Html\div;

$view = (
    div( // equal to Tag::div(...)
        'Hello ',
        Tag::a('PHP')->href('https://www.php.net')
    )->class('container')->style('background: #fff;')->data_key('primary')
);

echo $view->TDom();
```

The above code will output:

```html
<div class="container" style="background: #fff;" data-key="primary">
    Hello
    <a href="https://www.php.net">PHP</a>
</div>
```

## Component

You can use Tiny to encapsulate repeated code snippets into a functional component, which looks a lot like a React functional component:

```php
<?php

use function Tiny\Html\div;
use function Tiny\Html\h2;
use function Tiny\Html\h3;
use function Tiny\Html\a;
use function Tiny\Html\p;

function Section($props)
{
    extract($props);
    return (
        div(
            h2($title)->class('pb-2 border-bottom'),
            div(...$contents)->class($classList)
        )->class('container px-4 py-5')
    );
}

function IconColumn($props)
{
    extract($props);
    return (
        div(
            div(
                Svg($icon)->class('bi')->width('1em')->height('1em')
            )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
            h3($title)->class('fs-2'),
            p($content),
            a(
                $linkText,
                Svg('chevron-right')->class('bi')->width('1em')->height('1em'),
            )->href($link)->class('icon-link d-inline-flex align-items-center')
        )->class('feature col')
    );
}

function HangingIcon($props)
{
    extract($props);
    return (
        div(
            div(
                Svg($icon)->class('bi')->width('1em')->height('1em')
            )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
            div(
                h3($title)->class('fs-2'),
                p($content),
                a($linkText)->href($link)->class('btn btn-primary')
            )
        )->class('col d-flex align-items-start')
    );
}

function Svg($icon)
{
    return(
        Tag::svg(
            Tag::use()->href("#$icon")
        )
    );
}

$view =(
    main(
        Section([
            'title' => 'Columns with icons',
            'contents' => array_map(fn($data) => IconColumn($data), $columnsData),
            'classList' => 'row g-4 py-5 row-cols-1 row-cols-lg-3'
        ]),
        Section([
            'title' => 'Hanging icons',
            'contents' => array_map(fn($data) => HangingIcon($data), $hangingData),
            'classList' => 'row g-4 py-5 row-cols-1 row-cols-lg-3'
        ]),
    )
)
```

## Examples

For more usage examples see [here](https://github.com/YonLJ/Tiny/tree/master/examples).

## License

MIT © YonLJ
