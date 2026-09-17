# Purephp

[![Tests](https://github.com/YonLD/purephp/workflows/Tests/badge.svg)](https://github.com/YonLD/purephp/actions)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Purephp is a PHP templating engine inspired by ReactJS functional components.

## 📖 Documentation

- **English**: [https://yonld.github.io/purephp/](https://yonld.github.io/purephp/)
- **中文**: [https://yonld.github.io/purephp/zh/](https://yonld.github.io/purephp/zh/)

## Why use Purephp?

To enjoy pure PHP programming.

In traditional approaches, mixing HTML code, PHP code, and other template syntax in the view layer can be frustrating for developers.

However, with Purephp:
+ Everything is 100% native PHP code.
+ Encapsulate components to eliminate repetitive HTML code.
+ The syntax closely resembles HTML.
+ Compile data-free **shapes** into flat renderers, for performance on par with compiled template engines.

## Install

`composer require yonld/purephp`

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
[compiled rendering guide](https://yonld.github.io/purephp/guide/compiled) for
caching, conditionals and heterogeneous lists.

A shape renders with `$shape($data)` (string) or `$shape->print($data)`
(stdout); `$shape->save($path, $data)` writes a file and prepends the document
header of the root tag.

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
)->class('container')->style('background: #fff;')->data_key('primary')->print();
```

The above code will output:

```html
<div class="container" style="background: #fff;" data-key="primary">Hello <a href="https://www.php.net">PHP</a></div>
```

`render()` and `print()` are the debug/snippet outlet. Production pages
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

Nested components use `Slot::child()`, lists use `Slot::each()` (or
`Slot::eachKind()` for mixed item types), and conditionals use `Slot::if()`.
Everything else is plain PHP.

For production, `pure compile` precompiles shape files into `*.pure.php`
artifacts that return a `Renderer` without building the shape tree:

```bash
vendor/bin/pure compile src/shapes            # *.pure.php: the compiled renderer
vendor/bin/pure compile --plain src/shapes    # + *.plain.php: a dependency-free view
```

```php
$page = require __DIR__ . '/page.pure.php';

echo $page->render(['title' => 'Card Title']);        // the view body
echo $page->header . $page->render($data);            // the whole document
```

A `*.plain.php` view is markup and native PHP only — load it by extracting the
data into locals and nothing of purephp is needed at render time:

```php
ob_start();
extract($data, EXTR_SKIP);
require __DIR__ . '/views/index.plain.php';
$html = (string)ob_get_clean();
```

`pure compile --check` reports stale or missing artifacts for CI
(`--check --plain` covers both flavors). See
[Compiled Components](/guide/compiled#precompiled-artifacts) for the artifact
contract and the map closures it can copy.

## Examples

`examples/bootstrap-features` is a small MVC setup: `views/index.shape.php`
compiles into `views/index.pure.php` (strict artifact, loaded by `view()`) and
`views/index.plain.php` (dependency-free view, loaded by `plain()`). Two
controllers share the same view data through `indexData()`:
`app/controllers/IndexController.php` returns `view('index.pure', [...])` and
`app/controllers/PlainIndexController.php` returns the plain view. One router,
`public/index.php`, serves both: `/` redirects to `/plain`, `/pure` renders the
artifact and `/plain` the plain view.

```bash
vendor/bin/pure compile --plain examples/bootstrap-features
php -S localhost:8000 -t examples/bootstrap-features/public \
    examples/bootstrap-features/public/index.php
# http://localhost:8000/pure and http://localhost:8000/plain
```

The other examples render through the compiled path too; every artifact is
byte-identical to its shape. See
[here](https://github.com/YonLD/purephp/tree/master/examples).

## License

MIT © YonLD
