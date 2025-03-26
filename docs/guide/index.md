# 什么是 PurePHP?

PurePHP 是一个基于虚拟 DOM 的 PHP 模板引擎，灵感来自 ReactJS。它提供了一种声明式的方式来构建用户界面，使代码更加简洁、可维护和可重用。

## 特性

### 1. 声明式渲染

使用 PurePHP，你可以用声明式的方式描述你的 UI：

```php
<?php

use function Pure\HTML\{div, h1, p};

div(
    h1('欢迎使用 PurePHP'),
    p('这是一个基于虚拟 DOM 的 PHP 模板引擎')
)->class('container')->toPrint();
```

### 2. 组件化开发

将 UI 拆分为独立、可重用的组件：

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
div(
    Card([
        'title' => '标题 1',
        'content' => '内容 1'
    ]),
    Card([
        'title' => '标题 2',
        'content' => '内容 2'
    ])
)->class('card-grid')->toPrint();
```

### 3. XML 支持

PurePHP 提供了强大的 XML 处理能力，让您可以轻松地生成和处理 XML 文档：

```php
<?php

use Pure\Core\XML;

// 创建 XML 元素
$xml = XML::customers(
    XML::customer(
        XML::name('Charter Group'),
        XML::address(
            XML::street('100 Main'),
            XML::city('Framingham'),
            XML::state('MA'),
            XML::zip('01701')
        )
    )->id('55000')
);

// 保存到文件
$xml->toSave('./example.xml');
```

这种方式让 XML 的生成变得简单直观，就像编写普通的 PHP 代码一样。您还可以使用数组数据来批量生成 XML 元素：

```php
<?php

use Pure\Core\XML;

$data = [
    [
        'street' => '100 Main',
        'city'   => 'Framingham',
        'state'  => 'MA',
        'zip'    => '01701'
    ],
    [
        'street' => '720 Prospect',
        'city'   => 'Framingham',
        'state'  => 'MA',
        'zip'    => '01701'
    ]
];

function Address(array $props)
{
    extract($props);
    return XML::address(
        array_map(fn($x) => call_user_func("\Pure\Core\XML::$x", $props[$x]), array_keys($props))
    );
}

$xml = XML::customers(
    XML::customer(
        XML::name('Charter Group'),
        array_map(fn($x) => Address($x), $data)
    )->id('55000')
);

$xml->toSave('./example.xml');
```

### 4. SVG 支持

内置完整的 SVG 标签支持：

```php
<?php

use function Pure\SVG\{svg, circle, rect};

svg(
    rect()
        ->x(10)
        ->y(10)
        ->width(80)
        ->height(80)
        ->fill('none')
        ->stroke('blue')
        ->stroke_width(2),
    circle()
        ->cx(50)
        ->cy(50)
        ->r(30)
        ->fill('red')
)->width(100)->height(100)->toPrint();
```

## 为什么选择 PurePHP?

### 1. 简单易用

PurePHP 的 API 设计简单直观，学习曲线平缓。即使没有 React 经验，也能快速上手。

### 2. 性能优秀

通过虚拟 DOM 和高效的更新算法，PurePHP 能够提供出色的渲染性能。

### 3. 组件化

组件化开发让代码更容易维护和复用，提高开发效率。

### 4. 类型安全

PurePHP 完全支持 PHP 的类型系统，提供更好的开发体验。

### 5. 轻量级

核心库体积小巧，没有多余的依赖，可以轻松集成到现有项目中。

## 快速开始

### 安装

```bash
composer require purephp/purephp
```

### 基本使用

```php
<?php

require 'vendor/autoload.php';

use function Pure\HTML\{div, h1, p};

// 创建页面
div(
    h1('欢迎'),
    p('开始使用 PurePHP 吧！')
)->class('container')->toPrint();
```

## 下一步

- [快速开始](/guide/getting-started) - 学习如何创建你的第一个 PurePHP 应用
- [安装](/guide/installation) - 了解如何安装和配置 PurePHP
- [基本概念](/guide/concepts) - 深入理解 PurePHP 的核心概念
