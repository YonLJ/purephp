# Purephp

[![Tests](https://github.com/YonLJ/purephp/workflows/Tests/badge.svg)](https://github.com/YonLJ/purephp/actions)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Purephp is a PHP templating engine inspired by ReactJS functional components.

## 📖 Documentation

- **English**: [https://yonlj.github.io/purephp/](https://yonlj.github.io/purephp/)
- **中文**: [https://yonlj.github.io/purephp/zh/](https://yonlj.github.io/purephp/zh/)

## Why use Purephp?

To enjoy pure PHP programming.

In traditional approaches, mixing HTML code, PHP code, and other template syntax in the view layer can be frustrating for developers.

However, with Purephp:
+ Everything is 100% native PHP code.
+ Encapsulate components to eliminate repetitive HTML code.
+ The syntax closely resembles HTML.
+ Compile data-free **shapes** into flat renderers, for performance on par with compiled template engines.

## Install

`composer require yonlj/purephp`

## Quick start

Describe the page once as a data-free **shape** (a tag tree with `Slot`
placeholders), compile it once per process, then render it per request with
plain data:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{a, div, li, ul};

function ItemShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(li(Slot::text('label')));
}

function PageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            a('PHP')->href('https://www.php.net'),
            ul(Slot::each('items', ItemShape()))
        )->class('container')
    );
}

PageShape()->print([
    'items' => [['label' => 'Compiled'], ['label' => 'rendering']],
]);
```

The above code will output:

```html
<div class="container"><a href="https://www.php.net">PHP</a><ul><li>Compiled</li><li>rendering</li></ul></div>
```

Shapes are memoized in `static` variables and compiled once per PHP process;
requests only bind data. In long-running workers (or with `opcache.preload`)
that means once per worker. Under standard PHP-FPM every request starts fresh,
so enable `Compile::cachePath()` to load generated renderers instead of
rebuilding them. See the
[compiled rendering guide](https://yonlj.github.io/purephp/guide/compiled) for
caching, conditionals and heterogeneous lists.

## Snippets and debugging

For small fragments, one-off snippets and debugging you can build a regular tag
tree and render it immediately:

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

`render()` and `toPrint()` are the debug/snippet outlet. Production pages
should compile shapes, because a shape is compiled and static markup is escaped
once instead of on every render.

## Compiled components

Component shapes take static props as function arguments and dynamic props as
slots:

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, p};

function CardShape(string $classList = 'card'): Shape
{
    static $shapes = [];

    return $shapes[$classList] ??= Compile::shape(
        div(
            h2(Slot::text('title')),
            p(Slot::text('content'))
        )->class($classList)
    );
}

CardShape()->print([
    'title' => 'Card Title',
    'content' => 'Card Content',
]);
```

Nested components use `Slot::sub()`, lists use `Slot::each()` (or
`Slot::eachAny()` for mixed item types), and conditionals use `Slot::if()`.
Everything else is plain PHP.

## Examples

For more usage examples see [here](https://github.com/YonLJ/purephp/tree/master/examples).
Every example renders through the compiled path.

## License

MIT © YonLJ
