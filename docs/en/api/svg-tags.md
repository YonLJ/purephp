# SVG Tags

This guide documents all available SVG tag functions in PurePHP.

## Basic SVG Elements

### svg
```php
<?php

use function Pure\SVG\{svg};

// Basic SVG element
svg(
    rect()
        ->x(10)
        ->y(10)
        ->width(100)
        ->height(100)
        ->fill('red')
)->width(200)->height(200)->toPrint();

// SVG with viewBox
svg(
    rect()
        ->x(10)
        ->y(10)
        ->width(100)
        ->height(100)
        ->fill('blue')
)->width(200)->height(200)->viewBox('0 0 200 200')->toPrint();
```

## Basic Shapes

### rect
```php
<?php

use function Pure\SVG\{svg, rect};

// Basic rectangle
svg(
    rect()
        ->x(10)
        ->y(10)
        ->width(100)
        ->height(100)
        ->fill('red')
        ->stroke('black')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();

// Rounded rectangle
svg(
    rect()
        ->x(10)
        ->y(10)
        ->width(100)
        ->height(100)
        ->rx(10)
        ->ry(10)
        ->fill('blue')
        ->stroke('black')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();
```

### circle
```php
<?php

use function Pure\SVG\{svg, circle};

// Basic circle
svg(
    circle()
        ->cx(100)
        ->cy(100)
        ->r(50)
        ->fill('green')
        ->stroke('black')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();
```

### ellipse
```php
<?php

use function Pure\SVG\{svg, ellipse};

// Basic ellipse
svg(
    ellipse()
        ->cx(100)
        ->cy(50)
        ->rx(100)
        ->ry(50)
        ->fill('yellow')
        ->stroke('black')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();
```

### line
```php
<?php

use function Pure\SVG\{svg, line};

// Basic line
svg(
    line()
        ->x1(0)
        ->y1(0)
        ->x2(200)
        ->y2(200)
        ->stroke('black')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();
```

### polyline
```php
<?php

use function Pure\SVG\{svg, polyline};

// Basic polyline
svg(
    polyline()
        ->points('0,40 40,40 40,80 80,80 80,120 120,120 120,160')
        ->fill('none')
        ->stroke('black')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();
```

### polygon
```php
<?php

use function Pure\SVG\{svg, polygon};

// Basic polygon
svg(
    polygon()
        ->points('50,0 100,50 50,100 0,50')
        ->fill('purple')
        ->stroke('black')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();
```

## Text and Path

### text
```php
<?php

use function Pure\SVG\{svg, text};

// Basic text
svg(
    text('Hello SVG!')
        ->x(50)
        ->y(50)
        ->fontSize(20)
        ->fill('black')
)->width(200)->height(200)->toPrint();

// Text with attributes
svg(
    text('Styled Text')
        ->x(50)
        ->y(50)
        ->fontSize(24)
        ->fontFamily('Arial')
        ->fontWeight('bold')
        ->fill('blue')
        ->stroke('black')
        ->strokeWidth(1)
)->width(200)->height(200)->toPrint();
```

### path
```php
<?php

use function Pure\SVG\{svg, path};

// Basic path
svg(
    path()
        ->d('M 10 10 L 90 10 L 90 90 L 10 90 Z')
        ->fill('none')
        ->stroke('black')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();

// Complex path
svg(
    path()
        ->d('M 10 80 C 40 10, 65 10, 95 80 S 150 150, 180 80')
        ->fill('none')
        ->stroke('red')
        ->strokeWidth(2)
)->width(200)->height(200)->toPrint();
```

## Groups and Transformations

### g
```php
<?php

use function Pure\SVG\{svg, g, rect, circle};

// Group with transformation
svg(
    g(
        rect()
            ->x(10)
            ->y(10)
            ->width(50)
            ->height(50)
            ->fill('red'),
        circle()
            ->cx(35)
            ->cy(35)
            ->r(20)
            ->fill('blue')
    )->transform('translate(50, 50) rotate(45)')
)->width(200)->height(200)->toPrint();
```

### use
```php
<?php

use function Pure\SVG\{svg, defs, rect, use};

// Define and reuse elements
svg(
    defs(
        rect()
            ->id('myRect')
            ->width(50)
            ->height(50)
            ->fill('red')
    ),
    use()
        ->href('#myRect')
        ->x(10)
        ->y(10),
    use()
        ->href('#myRect')
        ->x(70)
        ->y(10)
)->width(200)->height(200)->toPrint();
```

## Gradients and Patterns

### Linear Gradient
```php
<?php

use function Pure\SVG\{svg, defs, linearGradient, stop, rect};

// Linear gradient
svg(
    defs(
        linearGradient(
            stop()->offset('0%')->stopColor('red'),
            stop()->offset('100%')->stopColor('blue')
        )->id('myGradient')
    ),
    rect()
        ->x(10)
        ->y(10)
        ->width(100)
        ->height(100)
        ->fill('url(#myGradient)')
)->width(200)->height(200)->toPrint();
```

### Radial Gradient
```php
<?php

use function Pure\SVG\{svg, defs, radialGradient, stop, circle};

// Radial gradient
svg(
    defs(
        radialGradient(
            stop()->offset('0%')->stopColor('red'),
            stop()->offset('100%')->stopColor('blue')
        )->id('myRadialGradient')
    ),
    circle()
        ->cx(100)
        ->cy(100)
        ->r(50)
        ->fill('url(#myRadialGradient)')
)->width(200)->height(200)->toPrint();
```

## Filters and Effects

### filter
```php
<?php

use function Pure\SVG\{svg, defs, filter, feGaussianBlur, feOffset, feMerge, feMergeNode, rect};

// Drop shadow effect
svg(
    defs(
        filter(
            feGaussianBlur()
                ->in('SourceAlpha')
                ->stdDeviation(3),
            feOffset()
                ->dx(2)
                ->dy(2),
            feMerge(
                feMergeNode(),
                feMergeNode()->in('SourceGraphic')
            )
        )->id('dropShadow')
    ),
    rect()
        ->x(10)
        ->y(10)
        ->width(100)
        ->height(100)
        ->fill('red')
        ->filter('url(#dropShadow)')
)->width(200)->height(200)->toPrint();
```

## Next Steps

- [HTML Tags](/en/api/html-tags) - Learn about HTML tag functions
- [Core API](/en/api/core) - Understand core API functions
- [Components](/en/guide/components) - Learn about component development
