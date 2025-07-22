# 工具函数

PurePHP 提供了一些实用的工具函数来简化开发，这些函数在设置元素属性时会自动使用。

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

div('内容')
    ->class('btn', 'btn-primary', $isActive ? 'active' : null, $size)
    ->toPrint();

// 等同于
use function Pure\Utils\clx;

$classes = clx('btn', 'btn-primary', $isActive ? 'active' : null, $size);
div('内容')->class($classes)->toPrint();
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

div('内容')
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
div('内容')->style($styles)->toPrint();
```

## rawHtml 函数

`rawHtml` 函数用于插入原始 HTML 内容，不会被转义。

### 基本用法

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

div(
    rawHtml('<strong>这是粗体文本</strong>'),
    rawHtml('<em>这是斜体文本</em>')
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

```php
<?php

use function Pure\HTML\button;

function Button($props) {
    [
        'text' => $text,
        'variant' => $variant = 'primary',
        'size' => $size = 'medium',
        'disabled' => $disabled = false,
        'loading' => $loading = false
    ] = $props;

    return button($loading ? '加载中...' : $text)
        ->class(
            'btn',
            "btn-{$variant}",
            "btn-{$size}",
            $disabled ? 'disabled' : null,
            $loading ? 'loading' : null
        )
        ->style([
            'opacity' => $disabled ? 0.6 : 1,
            'cursor' => $disabled ? 'not-allowed' : 'pointer'
        ])
        ->disabled($disabled);
}

// 使用示例
Button([
    'text' => '提交',
    'variant' => 'success',
    'size' => 'large',
    'loading' => false
])->toPrint();
```

### 响应式卡片组件

```php
<?php

use function Pure\HTML\{div, h3, p};

function Card($props) {
    [
        'title' => $title,
        'content' => $content,
        'featured' => $featured = false,
        'theme' => $theme = 'light'
    ] = $props;

    return div(
        h3($title)->class('card-title'),
        p($content)->class('card-content')
    )
    ->class(
        'card',
        "card-{$theme}",
        $featured ? 'card-featured' : null
    )
    ->style([
        'border-width' => $featured ? '2px' : '1px',
        'box-shadow' => $featured ? '0 4px 12px rgba(0,0,0,0.15)' : '0 2px 4px rgba(0,0,0,0.1)',
        'background-color' => $theme === 'dark' ? '#333' : '#fff',
        'color' => $theme === 'dark' ? '#fff' : '#333'
    ]);
}

// 使用示例
Card([
    'title' => '特色卡片',
    'content' => '这是一个特色卡片的内容',
    'featured' => true,
    'theme' => 'dark'
])->toPrint();
```

## 下一步

- [基本用法](/zh/guide/basic-usage) - 学习基础语法和用法
- [组件](/zh/guide/components) - 学习如何创建和使用组件
- [TailwindCSS 集成](/zh/guide/tailwindcss) - 了解如何与 TailwindCSS 配合使用
