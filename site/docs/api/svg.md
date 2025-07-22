# SVG Class

`Pure\Core\SVG` extends the XML class, specifically for SVG tags.

## Creating SVG Elements

Similar to HTML, SVG elements can be created in two ways:

### 1. Magic Static Methods

```php
<?php

use Pure\Core\SVG;

// Use SVG class directly with magic methods
$circle = SVG::circle()->cx('50')->cy('50')->r('40')->fill('red');
$rect = SVG::rect()->x('10')->y('10')->width('80')->height('80')->fill('blue');
$svg = SVG::svg($circle, $rect)->width('100')->height('100');
```

### 2. Constructor Method

```php
<?php

use Pure\Core\SVG;

// Use constructor directly
$circle = (new SVG('circle'))->cx('50')->cy('50')->r('40')->fill('red');
$rect = (new SVG('rect'))->x('10')->y('10')->width('80')->height('80')->fill('blue');
$svg = (new SVG('svg', [$circle, $rect]))->width('100')->height('100');
```

## Self-Closing Tags

The SVG class automatically recognizes the following self-closing tags:
- `animate`, `animateMotion`, `circle`, `ellipse`, `feBlend`, `feColorMatrix`, `feDisplacementMap`, `feDropShadow`, `feGaussianBlur`, `feImage`, `image`, `line`, `mpath`, `path`, `polygon`, `polyline`, `rect`, `stop`, `use`

```php
<?php

use function Pure\SVG\{svg, circle, rect};

$graphic = svg(
    circle()->cx('50')->cy('50')->r('40')->fill('red'),
    rect()->x('10')->y('10')->width('80')->height('80')->fill('blue')
)->width('100')->height('100');
```

## Examples

### Basic Shapes

```php
<?php

use function Pure\SVG\{svg, circle, rect, line, polygon};

$shapes = svg(
    // Circle
    circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->fill('red')
        ->stroke('black')
        ->stroke_width('2'),
    
    // Rectangle
    rect()
        ->x('120')
        ->y('10')
        ->width('80')
        ->height('80')
        ->fill('blue')
        ->rx('10'),
    
    // Line
    line()
        ->x1('220')
        ->y1('10')
        ->x2('280')
        ->y2('90')
        ->stroke('green')
        ->stroke_width('3'),
    
    // Polygon (triangle)
    polygon()
        ->points('300,10 340,90 260,90')
        ->fill('yellow')
        ->stroke('orange')
        ->stroke_width('2')
)->width('400')->height('100')->viewBox('0 0 400 100');

echo $shapes;
```

### Icons

```php
<?php

use function Pure\SVG\{svg, path};

function homeIcon(): SVG
{
    return svg(
        path('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6')
            ->stroke('currentColor')
            ->stroke_width('2')
            ->fill('none')
            ->stroke_linecap('round')
            ->stroke_linejoin('round')
    )->width('24')->height('24')->viewBox('0 0 24 24');
}

echo homeIcon()->class('icon');
```

### Animations

```php
<?php

use Pure\Core\SVG;

$animatedCircle = SVG::svg(
    SVG::circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->fill('red'),
    SVG::animate()
        ->attributeName('r')
        ->values('40;45;40')
        ->dur('2s')
        ->repeatCount('indefinite')
)->width('100')->height('100');

echo $animatedCircle;
```

### Gradients and Filters

```php
<?php

use function Pure\SVG\{svg, defs, linearGradient, stop, rect};

$gradientRect = svg(
    defs(
        linearGradient(
            stop()->offset('0%')->stop_color('#ff0000'),
            stop()->offset('100%')->stop_color('#0000ff')
        )->id('gradient1')
    ),
    rect()
        ->x('10')
        ->y('10')
        ->width('80')
        ->height('80')
        ->fill('url(#gradient1)')
)->width('100')->height('100');

echo $gradientRect;
```

### Custom SVG Components

```php
<?php

use Pure\Core\SVG;

// Create custom SVG elements using magic methods
$customElement = SVG::customShape(
    SVG::innerPath('M10,10 L90,90'),
    SVG::customAttribute('special-value')
)->data_type('custom')->class('special-svg');

echo $customElement;
```
