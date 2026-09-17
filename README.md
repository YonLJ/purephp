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

A component is one file: a function with typed props that returns `Raw`, plus
the template it renders, registered lazily so `pure compile` can precompile it:

```php
<?php

// components/Card.cmp.php

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h2(Slot::text('title')),
        p(Slot::text('content'))
    )->class('card')
));

function Card(string $title, string $content): Raw
{
    return render('Card', title: $title, content: $content);
}

echo Card('Card Title', 'Card Content');
```

The above code will output:

```html
<div class="card"><h2>Card Title</h2><p>Card Content</p></div>
```

`register()` only stores the factory; a request that finds a fresh artifact
never builds the template. Run `vendor/bin/pure compile components` to
precompile, and `pure compile --list` to see the units found. Pages register
with `registerPage()` and render with `renderPage()`, which prepends the
document header of the root tag.

Under standard PHP-FPM every request starts fresh, so enable
`Compile::cachePath()` (or precompile with `pure compile`) to load generated
renderers instead of rebuilding them; long-running workers keep them in memory.
See the [compiled rendering guide](https://yonlj.github.io/purephp/guide/compiled)
for caching, conditionals and heterogeneous lists.

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

Inside a template, nested shapes use `Slot::child()`, lists use `Slot::each()`
(or `Slot::eachKind()` for mixed item types), and conditionals use `Slot::if()`.
Everything else is plain PHP.

For production, `pure compile` precompiles every `*.cmp.php` unit (and every
lower-level `*.shape.php` template) into a `*.pure.php` artifact that returns a
`Renderer` without building the shape tree:

```bash
vendor/bin/pure compile components            # *.pure.php: the compiled renderer
vendor/bin/pure compile --plain components    # + *.plain.php: a dependency-free view
vendor/bin/pure compile --list components     # name -> file (component|page)
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

`examples/bootstrap` is a small MVC setup with three pages behind one router.
`views/features.cmp.php` and `views/pricing.cmp.php` are page units that compile
into a strict artifact (`*.pure.php`, loaded by `renderPage()`) and a
dependency-free view (`*.plain.php`, loaded by `plain()`); the two controllers
of a page share its view data through `featuresData()` / `pricingData()`. The
cover page is static markup through the string renderer (`views/cover.php`), so
it has neither variant. Routes:

```
/cover             the static cover page
/plain/features    the plain features view
/plain/pricing     the plain pricing view
/pure/features     the compiled features artifact
/pure/pricing      the compiled pricing artifact
```

```bash
vendor/bin/pure compile --plain examples/bootstrap
php -S localhost:8000 -t examples/bootstrap/public \
    examples/bootstrap/public/index.php
# http://localhost:8000/cover, /features and /pricing
```

A request that matches nothing gets a 404 that lists every route.

`event-counter` and `xml` follow the same layout — a `views/<page>.cmp.php`
unit plus a `public/index.php` router for `/`, `/pure` and `/plain` — and `xml`
adds `write.php`, the CLI entry that writes `example.xml`.

Every artifact is byte-identical to its template, and every plain view to its
artifact. See [here](https://github.com/YonLJ/purephp/tree/master/examples).

## License

MIT © YonLJ
