# PurePHP 简介

PurePHP 是一个受 ReactJS 启发的 PHP 虚拟 DOM 模板引擎。它旨在让 PHP 开发者能够以更现代和优雅的方式构建 Web 界面。

## 为什么选择 PurePHP？

在传统的 PHP 开发中，视图层通常需要混合 HTML、PHP 代码和其他模板语法，这种方式可能会让开发者感到困扰。PurePHP 通过以下方式解决了这些问题：

- **纯 PHP 实现**：所有代码都是 100% 原生 PHP，无需学习新的模板语法
- **组件化开发**：通过组件封装消除重复的 HTML 代码
- **类 HTML 语法**：使用与 HTML 非常相似的语法，降低学习成本
- **虚拟 DOM**：基于虚拟 DOM 实现，确保高效的渲染性能

## 核心特性

### 1. 声明式渲染

PurePHP 使用声明式的方式描述 UI，让代码更易读和维护：

```php
<?php

use function Pure\HTML\div;
use function Pure\HTML\h1;

div(
    h1('欢迎使用 PurePHP')
)->class('container')->toPrint();
```

### 2. 组件化开发

支持将重复的 UI 代码封装成可复用的组件：

```php
function Card($title, $content) {
    return div(
        h2($title),
        p($content)
    )->class('card');
}
```

### 3. 属性链式调用

支持链式调用设置元素属性：

```php
div('内容')
    ->class('container')
    ->style('background: #fff;')
    ->data_key('primary')
    ->toPrint();
```

### 4. 支持 SVG

内置 SVG 标签支持，方便创建矢量图形：

```php
use function Pure\SVG\svg;
use function Pure\SVG\circle;

svg(
    circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->stroke('black')
        ->stroke_width('3')
        ->fill('red')
)->width('100')->height('100')->toPrint();
```

## 快速预览

这是一个简单的示例，展示 PurePHP 的基本用法：

```php
<?php

use function Pure\HTML\div;
use function Pure\HTML\a;

div(
    'Hello ',
    a('PHP')->href('https://www.php.net')
)->class('container')->style('background: #fff;')->data_key('primary')->toPrint();
```

输出结果：

```html
<div class="container" style="background: #fff;" data-key="primary">
    Hello <a href="https://www.php.net">PHP</a>
</div>
```

## 下一步

- [快速开始](/guide/getting-started) - 了解如何安装和使用 PurePHP
- [基础用法](/guide/basic-usage) - 学习 PurePHP 的基本概念和用法
- [组件开发](/guide/components) - 深入了解组件化开发
- [最佳实践](/guide/best-practices) - 查看推荐的项目结构和开发方式
