# Purephp

[![Tests](https://github.com/YonLJ/purephp/workflows/Tests/badge.svg)](https://github.com/YonLJ/purephp/actions)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Purephp is a PHP templating engine inspired by ReactJS functional components.

## 📖 Documentation

- **English**: [https://yonlj.github.io/purephp/en/](https://yonlj.github.io/purephp/en/)
- **中文**: [https://yonlj.github.io/purephp/](https://yonlj.github.io/purephp/)

## Why use Purephp?

To enjoy pure PHP programming.

In traditional approaches, mixing HTML code, PHP code, and other template syntax in the view layer can be frustrating for developers.

However, with Purephp:
+ Everything is 100% native PHP code.
+ Encapsulate components to eliminate repetitive HTML code.
+ The syntax closely resembles HTML.

## Install

`composer require yonlj/purephp`

## Basic usage

Here is a simple example that will show how to use `Purephp`:

```php
<?php

use function Pure\HTML\a;
use function Pure\HTML\div;

div(
    'Hello ',
    a('PHP')->href('https://www.php.net')
)->class('container')->style('background: #fff;')->data_key('primary')->toPrint();
```

The above code will output:

```html
<div class="container" style="background: #fff;" data-key="primary">Hello <a href="https://www.php.net">PHP</a></div>
```

## Component

You can use Pure to encapsulate repeated code snippets into a functional component, which looks a lot like a React functional component:

```php
<?php

use function Pure\HTML\div;
use function Pure\HTML\h2;
use function Pure\HTML\h3;
use function Pure\HTML\a;
use function Pure\HTML\p;
use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

// use named arguments
function Section($title, $contents, $classList)
{
    return (
        div(
            h2($title)->class('pb-2 border-bottom'),
            div(...$contents)->class($classList)
        )->class('container px-4 py-5')
    );
}

// use array destructuring assignments
function IconColumn($props)
{
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

// use extract()
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

main(
    Section(
        title: 'Columns with icons',
        contents: array_map(fn($data) => IconColumn($data), $columnsData),
        classList: 'row g-4 py-5 row-cols-1 row-cols-lg-3'
    ),
    Section(
        title: 'Hanging icons',
        contents: array_map(fn($data) => HangingIcon($data), $hangingData),
        classList: 'row g-4 py-5 row-cols-1 row-cols-lg-3'
    ),
)->toPrint();
```

## Examples

For more usage examples see [here](https://github.com/YonLJ/purephp/tree/master/examples).

## License

MIT © YonLJ
