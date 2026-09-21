# What is PurePHP?

PurePHP is a PHP template engine inspired by ReactJS functional components. You describe a UI as a tree of PHP objects that look like HTML, and PurePHP turns it into an HTML string — everything is 100% native PHP, no template syntax to learn.

PurePHP has two rendering paths:

| Path | What you write | When to use |
| --- | --- | --- |
| **Compiled rendering** | A data-free *shape* tree with `Slot` placeholders, compiled once per worker process (or loaded from the renderer cache) and rendered per request with plain data | Pages and components in production |
| **Immediate rendering** | A tag tree containing the real values, rendered on the spot with `render()` / `print()` | Snippets, prototypes, CLI tools and debugging |

## Why Choose PurePHP?

In traditional PHP development, the view layer often requires mixing HTML, PHP code, and other template syntax, which can be confusing for developers. PurePHP solves these problems by:

- **Pure PHP Implementation**: All code is 100% native PHP, no new template syntax to learn
- **Component-Based Development**: Reusable component shapes instead of repetitive HTML
- **HTML-like Syntax**: Tag helpers and method chaining look very close to HTML
- **Compiled Rendering**: Static markup is escaped once at compile time, so rendering costs little more than string concatenation

## Compiled Rendering

Describe the page once, bind data at render time:

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\HTML;
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, p};

function pageView(array $data): string
{
    static $render;
    $render ??= Compile::shape(
        div(
            h1(Slot::value('heading')),
            p(Slot::value('lead'))
        )->class('container')
    );

    // No document header is added by the engine; prepend it here.
    return HTML::DOCUMENT_HEADER . $render([
        'heading' => $data['heading'],
        'lead' => $data['lead'],
    ]);
}

echo pageView(['heading' => 'Welcome to PurePHP', 'lead' => 'A PHP template engine']);
```

The renderer is memoized in a `static` variable and compiled once per process —
in long-running workers. Under standard PHP-FPM every request starts fresh, so
enable `Compile::cachePath()` or precompile the template with
`vendor/bin/pure compile` so requests load the artifact instead of rebuilding
it. See [Components](/guide/components) and
[Compiled Components](/guide/compiled) for lists, conditionals and caching.

## Immediate Rendering

For snippets and debugging, build a tag tree with real values and print it:

```php
<?php

use function Pure\HTML\{div, h1, p};

div(
    h1('Welcome to PurePHP'),
    p('A PHP template engine')
)->class('container')->print();
```

## Advantages

1. **Simple and Easy**: API design is simple and intuitive with a gentle learning curve
2. **Fast**: Compiled shapes render at parity with compiled template engines
3. **Type Safe**: Full support for PHP's type system, providing better development experience
4. **Lightweight**: Small core library with no unnecessary dependencies

## Next Steps

- [Quick Start](/guide/getting-started) - Learn how to create your first PurePHP application
- [Compiled Components](/guide/compiled) - Build pages and components the production way
- [Core Concepts](/guide/concepts) - Understand PurePHP fundamentals
- [Basic Usage](/guide/basic-usage) - Learn the tag API used by snippets
