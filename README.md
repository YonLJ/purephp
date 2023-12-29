# Purephp

Purephp is a Virtual DOM based templating-engine for PHP inspired by ReactJS.

## What is it

Purephp is a template engine that can output HTML5 strings or files. It also works on xml and svg.

## Install

`composer require yonlj/purephp`

## Basic usage

Here is a simple example that will show how to use `Pure`:

```php
<?php

use Pure\Core\NDom;
use Pure\Core\HTML;
use function Pure\Tags\HTML\div;

$view = (
    div( // equal to HTML::div(...)
        'Hello ',
        HTML::a('PHP')->href('https://www.php.net')
    )->class('container')->style('background: #fff;')->data_key('primary')
);

echo $view->PDom();
```

The above code will output:

```html
<div class="container" style="background: #fff;" data-key="primary">Hello <a href="https://www.php.net">PHP</a></div>
```

## Component

You can use Pure to encapsulate repeated code snippets into a functional component, which looks a lot like a React functional component:

```php
<?php

use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\h2;
use function Pure\Tags\HTML\h3;
use function Pure\Tags\HTML\a;
use function Pure\Tags\HTML\p;
use function Pure\Tags\SVG\svg;
use function Pure\Tags\SVG\svgUse;

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
    [
        'icon' => $icon,
        'title' => $title,
        'content' => $content,
        'linkText' => $linkText,
        'link' => $link
    ] = $props;
    return (
        div(
            div(
                Icon($icon)->class('bi')->width('1em')->height('1em')
            )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
            h3($title)->class('fs-2'),
            p($content),
            a(
                $linkText,
                Icon('chevron-right')->class('bi')->width('1em')->height('1em'),
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
                Icon($icon)->class('bi')->width('1em')->height('1em')
            )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
            div(
                h3($title)->class('fs-2'),
                p($content),
                a($linkText)->href($link)->class('btn btn-primary')
            )
        )->class('col d-flex align-items-start')
    );
}

function Icon($icon)
{
    return(
        svg(
            svgUse()->href("#$icon")
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

For more usage examples see [here](https://github.com/YonLJ/Pure/tree/master/examples).

## License

MIT © YonLJ
