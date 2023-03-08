# Tiny
Tiny is a Virtual DOM based templating-engine for PHP inspired by ReactJS.

## What is it

Tiny is a template engine that can output HTML5 strings or files.

+ Tiny mainly consists of 3 parts: `Tag`, `VDom` and `tags`.
+ The `Tag` class is responsible for generating virtual Dom data.
+ The `VDom` class is responsible for rendering virtual dom data into real Dom.
+ The `tags` file provides a number of commonly used HTML5 tags, all of which are instances of the Tag class.

it also works on xml.

## Basic usage

Here is a simple example that will show how to use `Tiny`:

```php
<?php

use Tiny\VDom;
use Tiny\Tag;
use function Tiny\div;

$view = (
    div(
        'Hello ',
        Tag::a('PHP')->href('https://www.php.net')
    )->class('container')->style('background: #fff;')->data_key('primary')
);

$vDom = new VDom();
$vDom->outputHTML($view);
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

use function Tiny\div;
use function Tiny\h2;

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

// function IconColumn($props) {...}
// function HangingIcon($props) {...}

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

## Keywords

php templating-engine virtual-dom
