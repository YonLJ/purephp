# 属性系统

PurePHP 提供了强大的属性系统，用于配置和自定义组件的行为和外观。

## 基本属性

### 1. HTML 属性

```php
<?php

use function Pure\HTML\{div};

// 设置基本属性
div('内容')
    ->id('main')
    ->class('container')
    ->style('background: #fff;')
    ->toPrint();
```

### 2. 数据属性

```php
<?php

use function Pure\HTML\{div};

// 设置数据属性
div('内容')
    ->data_id('123')
    ->data_type('card')
    ->data_status('active')
    ->toPrint();
```

### 3. ARIA 属性

```php
<?php

use function Pure\HTML\{button};

// 设置 ARIA 属性
button('提交')
    ->aria_label('提交表单')
    ->aria_disabled('false')
    ->aria_required('true')
    ->toPrint();
```

## 属性链式调用

PurePHP 支持链式调用属性方法：

```php
<?php

use function Pure\HTML\{div};

div('内容')
    ->id('main')
    ->class('container')
    ->style('background: #fff;')
    ->data_type('card')
    ->aria_label('主要内容')
    ->toPrint();
```

## 动态属性

### 1. 条件属性

```php
<?php

use function Pure\HTML\{div};

function DynamicBox($props) {
    [
        'active' => $active = false,
        'disabled' => $disabled = false
    ] = $props;

    return div('内容')
        ->class('box')
        ->class($active ? 'active' : '')
        ->class($disabled ? 'disabled' : '')
        ->data_active($active)
        ->data_disabled($disabled);
}

// 使用组件
DynamicBox([
    'active' => true,
    'disabled' => false
])->toPrint();
```

### 2. 计算属性

```php
<?php

use function Pure\HTML\{div};

function ResponsiveBox($props) {
    [
        'width' => $width = 100,
        'height' => $height = 100
    ] = $props;

    $style = sprintf(
        'width: %dpx; height: %dpx; aspect-ratio: %d/%d;',
        $width,
        $height,
        $width,
        $height
    );

    return div('内容')
        ->style($style)
        ->class('responsive-box');
}

// 使用组件
ResponsiveBox([
    'width' => 200,
    'height' => 150
])->toPrint();
```

## 属性验证

### 1. 类型检查

```php
<?php

use function Pure\HTML\{div};

function ValidatedBox($props) {
    [
        'width' => $width,
        'height' => $height,
        'color' => $color
    ] = $props;

    // 验证属性类型
    if (!is_numeric($width) || !is_numeric($height)) {
        throw new \InvalidArgumentException('宽度和高度必须是数字');
    }

    if (!is_string($color)) {
        throw new \InvalidArgumentException('颜色必须是字符串');
    }

    return div('内容')
        ->style("width: {$width}px; height: {$height}px; background: {$color};");
}

// 使用组件
try {
    ValidatedBox([
        'width' => 200,
        'height' => 150,
        'color' => '#ff0000'
    ])->toPrint();
} catch (\InvalidArgumentException $e) {
    echo "错误: {$e->getMessage()}";
}
```

### 2. 必填属性

```php
<?php

use function Pure\HTML\{div};

function RequiredBox($props) {
    // 检查必填属性
    $required = ['id', 'type'];
    foreach ($required as $prop) {
        if (!isset($props[$prop])) {
            throw new \InvalidArgumentException("{$prop} 是必需的属性");
        }
    }

    return div('内容')
        ->id($props['id'])
        ->data_type($props['type']);
}

// 使用组件
try {
    RequiredBox([
        'id' => 'box1',
        'type' => 'card'
    ])->toPrint();
} catch (\InvalidArgumentException $e) {
    echo "错误: {$e->getMessage()}";
}
```

## 默认属性

### 使用默认值

```php
<?php

use function Pure\HTML\div;

function CustomComponent($props) {
    // 设置默认属性
    $defaultProps = [
        'theme' => 'light',
        'size' => 'medium',
        'disabled' => false
    ];

    $props = array_merge($defaultProps, $props);

    [
        'theme' => $theme,
        'size' => $size,
        'disabled' => $disabled,
        'content' => $content
    ] = $props;

    return div($content)
        ->class("custom-component theme-{$theme} size-{$size}")
        ->data_disabled($disabled ? 'true' : 'false');
}

// 使用组件
CustomComponent([
    'theme' => 'dark',
    'size' => 'large',
    'content' => '自定义内容'
])->toPrint();
```

### 2. 属性合并

```php
<?php

use function Pure\HTML\{div};

function MergedBox($props) {
    [
        'class' => $class = '',
        'style' => $style = '',
        'data' => $data = []
    ] = $props;

    // 合并类名
    $classes = array_merge(
        ['box'],
        explode(' ', $class)
    );

    // 合并样式
    $styles = array_merge(
        ['background: #fff;'],
        explode(';', $style)
    );

    // 合并数据属性
    $dataAttrs = array_merge(
        ['type' => 'box'],
        $data
    );

    return div('内容')
        ->class(implode(' ', array_filter($classes)))
        ->style(implode(';', array_filter($styles)))
        ->data($dataAttrs);
}

// 使用组件
MergedBox([
    'class' => 'custom-box',
    'style' => 'color: #000;',
    'data' => ['status' => 'active']
])->toPrint();
```

## 属性转换

### 1. 类型转换

```php
<?php

use function Pure\HTML\{div};

function TypedBox($props) {
    [
        'width' => $width,
        'height' => $height,
        'opacity' => $opacity
    ] = $props;

    // 转换属性类型
    $width = (int) $width;
    $height = (int) $height;
    $opacity = (float) $opacity;

    return div('内容')
        ->style("width: {$width}px; height: {$height}px; opacity: {$opacity};");
}

// 使用组件
TypedBox([
    'width' => '200',
    'height' => '150',
    'opacity' => '0.5'
])->toPrint();
```

### 2. 值转换

```php
<?php

use function Pure\HTML\{div};

function TransformedBox($props) {
    [
        'color' => $color,
        'size' => $size
    ] = $props;

    // 转换颜色值
    $color = str_starts_with($color, '#') ? $color : "#{$color}";

    // 转换尺寸值
    $size = str_ends_with($size, 'px') ? $size : "{$size}px";

    return div('内容')
        ->style("color: {$color}; font-size: {$size};");
}

// 使用组件
TransformedBox([
    'color' => 'ff0000',
    'size' => '16'
])->toPrint();
```

## 下一步

- [事件](/zh/guide/events) - 学习事件处理
- [工具函数](/zh/guide/utils) - 了解内置的工具函数
- [组件](/zh/guide/components) - 深入学习组件开发
