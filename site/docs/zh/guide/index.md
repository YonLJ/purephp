# 什么是 PurePHP?

PurePHP 是一个受 ReactJS 函数式组件启发的 PHP 模板引擎。它使用 PHP 对象来表示 HTML 元素，提供了一种声明式的方式来构建用户界面，使代码更加简洁、可维护和可重用。

## 为什么选择 PurePHP？

在传统的 PHP 开发中，视图层通常需要混合 HTML、PHP 代码和其他模板语法，这种方式可能会让开发者感到困扰。PurePHP 通过以下方式解决了这些问题：

- **纯 PHP 实现**：所有代码都是 100% 原生 PHP，无需学习新的模板语法
- **组件化开发**：通过 PHP 函数封装消除重复的 HTML 代码
- **类 HTML 语法**：使用与 HTML 非常相似的语法，降低学习成本
- **对象转换**：使用 PHP 对象表示 HTML 元素，然后转换为 HTML 字符串

## 核心特性

### 1. 声明式渲染

PurePHP 使用声明式的方式描述 UI，让代码更易读和维护：

```php
<?php

use function Pure\HTML\{div, h1, p};

div(
    h1('欢迎使用 PurePHP'),
    p('这是一个 PHP 模板引擎')
)->class('container')->toPrint();
```

### 2. 组件化开发

将 UI 拆分为独立、可重用的 PHP 函数：

```php
<?php

use function Pure\HTML\{div, h2, p};

function Card($props) {
    [
        'title' => $title,
        'content' => $content
    ] = $props;

    return div(
        h2($title),
        p($content)
    )->class('card');
}

// 使用组件
Card([
    'title' => '卡片标题',
    'content' => '卡片内容'
])->toPrint();
```

### 3. 属性链式调用

支持链式调用设置元素属性：

```php
<?php

use function Pure\HTML\div;

div('内容')
    ->class('container')
    ->style('background: #fff;')
    ->data_id('main')
    ->toPrint();
```

## 优势

1. **简单易用**：API 设计简单直观，学习曲线平缓
2. **类型安全**：完全支持 PHP 的类型系统，提供更好的开发体验
3. **轻量级**：核心库体积小巧，没有多余的依赖
4. **组件化**：组件化开发让代码更容易维护和复用

## 下一步

- [快速开始](/zh/guide/getting-started) - 学习如何创建你的第一个 PurePHP 应用
- [基本概念](/zh/guide/concepts) - 深入理解 PurePHP 的核心概念
- [基本用法](/zh/guide/basic-usage) - 学习基础语法和用法
