# 工具函数

PurePHP 提供了一些实用的工具函数来简化开发，这些函数在设置元素属性时会自动使用。

*`clx()`、`sty()` 和 `rawHtml()` 不受编译渲染影响：在形状中构建静态属性时照常使用，动态值则通过 `Slot::text()` / `Slot::attr()` / `Slot::raw()` 绑定——参见[编译组件](/zh/guide/compiled)。下面的多数示例使用标签 API，它对代码片段和调试依然有效。*

## clx 函数

`clx` 函数用于合并类名，支持字符串、数组和条件类名。

### 基本用法

```php
<?php

use function Pure\Utils\clx;

// 合并多个字符串类名
$classes = clx('btn', 'btn-primary', 'large');
echo $classes; // 输出: btn btn-primary large
```

### 条件类名

```php
<?php

use function Pure\Utils\clx;

$isActive = true;
$isDisabled = false;

$classes = clx(
    'btn',
    $isActive ? 'active' : null,
    $isDisabled ? 'disabled' : null
);
echo $classes; // 输出: btn active
```

### 数组支持

```php
<?php

use function Pure\Utils\clx;

$classes = clx(
    'btn',
    [
        'btn-primary',
        'active' => true,
        'disabled' => false,
        'large' => null
    ]
);
echo $classes; // 输出: btn btn-primary active
```

### 在 class() 方法中的内置使用

`class()` 方法内置了 `clx` 函数，可以直接传递多个参数：

```php
<?php

use function Pure\HTML\div;

$isActive = true;
$size = 'large';

div('Content')
    ->class('btn', 'btn-primary', $isActive ? 'active' : null, $size)
    ->toPrint();

// 等同于
use function Pure\Utils\clx;

$classes = clx('btn', 'btn-primary', $isActive ? 'active' : null, $size);
div('Content')->class($classes)->toPrint();
```

## sty 函数

`sty` 函数用于将样式数组转换为 CSS 字符串。

### 基本用法

```php
<?php

use function Pure\Utils\sty;

$styles = sty([
    'background-color' => 'red',
    'height' => '36px',
    'border' => '1px solid #fff'
]);
echo $styles; // 输出: background-color: red; height: 36px; border: 1px solid #fff;
```

### 条件样式

```php
<?php

use function Pure\Utils\sty;

$isVisible = true;
$color = 'blue';

$styles = sty([
    'color' => $color,
    'display' => $isVisible ? 'block' : 'none',
    'opacity' => $isVisible ? 1 : 0,
    'margin' => null,  // 会被忽略
    'padding' => false // 会被忽略
]);
echo $styles; // 输出: color: blue; display: block; opacity: 1;
```

### 在 style() 方法中的内置使用

`style()` 方法内置了 `sty` 函数，可以直接传递数组：

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->style([
        'background-color' => '#f0f0f0',
        'padding' => '20px',
        'border-radius' => '8px',
        'margin' => '10px 0'
    ])
    ->toPrint();

// 等同于
use function Pure\Utils\sty;

$styles = sty([
    'background-color' => '#f0f0f0',
    'padding' => '20px',
    'border-radius' => '8px',
    'margin' => '10px 0'
]);
div('Content')->style($styles)->toPrint();
```

## rawHtml 函数

`rawHtml` 函数用于插入原始 HTML 内容，不会被转义。

### 基本用法

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

div(
    rawHtml('<strong>This is bold text</strong>'),
    rawHtml('<em>This is italic text</em>')
)->toPrint();
```

### 注意事项

使用 `rawHtml` 时要确保内容是安全的，避免 XSS 攻击：

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// 安全的使用方式
$safeHtml = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
div(rawHtml($safeHtml))->toPrint();

// 或者使用已知安全的 HTML
$iconHtml = '<svg><path d="..."/></svg>';
div(rawHtml($iconHtml))->toPrint();
```

## 实际应用示例

### 动态按钮组件

静态配置是函数参数；标签文本和按钮状态则是槽位：

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\button;
use function Pure\Utils\sty;

function ButtonShape(string $variant = 'primary', string $size = 'medium', bool $loading = false): Shape
{
    static $shapes = [];

    return $shapes["{$variant}|{$size}|" . (int) $loading] ??= Compile::shape(
        button(Slot::text('text'))
            ->class('btn', "btn-{$variant}", "btn-{$size}", $loading ? 'loading' : null)
            ->style(Slot::attr('style'))
            ->disabled(Slot::attr('disabled'))
    );
}

// 绑定数据：仅为渲染时的值；为 null 的属性会被省略。
$bindings = [
    'text' => 'Submit',
    'style' => sty(['opacity' => 1, 'cursor' => 'pointer']),
    'disabled' => null,
];

ButtonShape('success', 'large')->print($bindings);
```

### 响应式卡片组件

卡片接受 HTML 子内容，因此其内容使用 `Slot::raw()` 绑定：

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h3, p};

function CardShape(string $theme = 'light', bool $featured = false): Shape
{
    static $shapes = [];

    return $shapes["{$theme}|" . (int) $featured] ??= Compile::shape(
        div(
            h3(Slot::text('title'))->class('card-title'),
            p(Slot::raw('content'))->class('card-content')
        )
        ->class('card', "card-{$theme}", $featured ? 'card-featured' : null)
        ->style([
            'border-width' => $featured ? '2px' : '1px',
            'box-shadow' => $featured ? '0 4px 12px rgba(0,0,0,0.15)' : '0 2px 4px rgba(0,0,0,0.1)',
            'background-color' => $theme === 'dark' ? '#333' : '#fff',
            'color' => $theme === 'dark' ? '#fff' : '#333'
        ])
    );
}

// 绑定数据：`content` 是可信 HTML，将原样输出。
$bindings = [
    'title' => 'Featured Card',
    'content' => '<strong>This is the content</strong> of a featured card',
];

CardShape('dark', true)->print($bindings);
```

## 下一步

- [基本用法](/zh/guide/basic-usage) - 学习基础语法和用法
- [组件](/zh/guide/components) - 学习如何创建和使用组件
- [TailwindCSS 集成](/zh/guide/tailwindcss) - 了解如何与 TailwindCSS 配合使用
