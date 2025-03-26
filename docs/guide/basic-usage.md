# 基础用法

本指南将介绍 PurePHP 的核心概念和基本用法。

## 基本语法

### 1. 创建 HTML 元素

PurePHP 使用函数调用的方式来创建 HTML 元素：

```php
<?php

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\p;

// 创建简单的 div 元素
div('Hello World')->toPrint();

// 创建嵌套的元素
div(
    h1('标题'),
    p('段落内容')
)->class('container')->toPrint();
```

### 2. 设置属性

使用链式调用设置元素属性：

```php
<?php

use function Pure\HTML\div;

div('内容')
    ->class('container')
    ->style('background: #fff;')
    ->data_key('primary')
    ->id('main')
    ->toPrint();
```

### 3. 添加子元素

可以通过参数传递添加多个子元素：

```php
<?php

use function Pure\HTML\div;
use function Pure\HTML\p;

div(
    p('第一段'),
    p('第二段'),
    p('第三段')
)->class('content')->toPrint();
```

## 常用 HTML 标签

PurePHP 支持所有常用的 HTML 标签：

```php
<?php

use function Pure\HTML\{
    div, span, p, h1, h2, h3, h4, h5, h6,
    a, img, ul, ol, li, table, tr, td, th,
    form, input, button, textarea, select, option
};

// 创建链接
a('点击这里')->href('https://example.com')->toPrint();

// 创建图片
img()->src('image.jpg')->alt('图片描述')->toPrint();

// 创建列表
ul(
    li('项目 1'),
    li('项目 2'),
    li('项目 3')
)->class('list')->toPrint();

// 创建表单
form(
    input()->type('text')->name('username'),
    input()->type('password')->name('password'),
    button('提交')->type('submit')
)->method('POST')->action('/login')->toPrint();
```

## SVG 支持

PurePHP 内置支持 SVG 标签：

```php
<?php

use function Pure\SVG\{svg, circle, rect, path};

// 创建简单的圆形
svg(
    circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->stroke('black')
        ->stroke_width('3')
        ->fill('red')
)->width('100')->height('100')->toPrint();

// 创建矩形
svg(
    rect()
        ->x('10')
        ->y('10')
        ->width('80')
        ->height('80')
        ->fill('blue')
)->width('100')->height('100')->toPrint();
```

## 条件渲染

使用 PHP 的条件语句进行条件渲染：

```php
<?php

use function Pure\HTML\{div, p};

$isLoggedIn = true;

div(
    $isLoggedIn ? p('欢迎回来！') : p('请登录')
)->class('message')->toPrint();
```

## 循环渲染

使用 PHP 的循环语句渲染列表：

```php
<?php

use function Pure\HTML\{ul, li};

$items = ['苹果', '香蕉', '橙子'];

ul(
    ...array_map(fn($item) => li($item), $items)
)->class('fruits')->toPrint();
```

## 样式处理

### 1. 内联样式

```php
<?php

use function Pure\HTML\div;

div('内容')
    ->style('
        background: #f0f0f0;
        padding: 20px;
        border-radius: 8px;
    ')
    ->toPrint();
```

### 2. 类名处理

```php
<?php

use function Pure\HTML\div;

$isActive = true;

div('内容')
    ->class('container')
    ->class($isActive ? 'active' : 'inactive')
    ->toPrint();
```

## 最佳实践

1. **组件化**：将重复的 UI 代码封装成组件
2. **属性链式调用**：使用链式调用提高代码可读性
3. **条件渲染**：使用 PHP 的条件语句进行条件渲染
4. **循环渲染**：使用 PHP 的循环语句处理列表渲染
5. **样式管理**：合理使用类名和样式属性

## 下一步

- [学习组件开发](/guide/components) - 了解如何创建和使用组件
- [探索高级特性](/guide/advanced) - 了解更多高级功能
- [查看 API 文档](/api/) - 了解所有可用的 API
