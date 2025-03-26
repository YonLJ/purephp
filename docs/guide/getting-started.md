# 快速开始

本指南将帮助你快速创建一个 PurePHP 应用。

## 环境要求

- PHP 7.4 或更高版本
- Composer

## 创建新项目

### 1. 创建项目目录

```bash
mkdir my-purephp-app
cd my-purephp-app
```

### 2. 初始化 Composer

```bash
composer init
```

### 3. 安装 PurePHP

```bash
composer require purephp/purephp
```

### 4. 创建入口文件

创建 `index.php` 文件：

```php
<?php

require 'vendor/autoload.php';

use function Pure\HTML\{div, h1, p, a};

// 创建页面布局
div(
    // 头部
    div(
        h1('我的 PurePHP 应用'),
        nav(
            a('首页')->href('/'),
            a('关于')->href('/about'),
            a('联系')->href('/contact')
        )->class('nav')
    )->class('header'),

    // 主要内容
    div(
        p('欢迎来到 PurePHP 的世界！'),
        p('这是一个简单的示例页面。')
    )->class('main'),

    // 底部
    div(
        p('© 2024 我的 PurePHP 应用')
    )->class('footer')
)->class('app')->toPrint();
```

### 5. 添加样式

创建 `style.css` 文件：

```css
.app {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.header {
    text-align: center;
    margin-bottom: 40px;
}

.nav {
    margin: 20px 0;
}

.nav a {
    margin: 0 10px;
    text-decoration: none;
    color: #333;
}

.nav a:hover {
    color: #007bff;
}

.main {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.footer {
    text-align: center;
    margin-top: 40px;
    padding: 20px;
    border-top: 1px solid #eee;
}
```

### 6. 在入口文件中引入样式

更新 `index.php`：

```php
<?php

require 'vendor/autoload.php';

use function Pure\HTML\{div, h1, p, a, link};

// 添加样式表
link()
    ->rel('stylesheet')
    ->href('style.css')
    ->toPrint();

// 创建页面布局
div(
    // 头部
    div(
        h1('我的 PurePHP 应用'),
        Navigation([
            'items' => [
                ['text' => '首页', 'href' => '/'],
                ['text' => '关于', 'href' => '/about'],
                ['text' => '联系', 'href' => '/contact']
            ]
        ])
    )->class('header'),

    // 主要内容
    div(
        Card([
            'title' => '欢迎',
            'content' => '这是一个使用 PurePHP 创建的示例页面。',
            'image' => 'welcome.jpg'
        ]),
        Card([
            'title' => '特性',
            'content' => 'PurePHP 提供了许多强大的特性，帮助你构建现代化的 Web 应用。'
        ])
    )->class('main'),

    // 底部
    div(
        p('© 2024 我的 PurePHP 应用')
    )->class('footer')
)->class('app')->toPrint();
```

## 创建组件

### 1. 创建组件目录

```bash
mkdir components
```

### 2. 创建导航组件

创建 `components/Navigation.php`：

```php
<?php

use function Pure\HTML\{nav, a};

function Navigation($props) {
    [
        'items' => $items = [
            ['text' => '首页', 'href' => '/'],
            ['text' => '关于', 'href' => '/about'],
            ['text' => '联系', 'href' => '/contact']
        ]
    ] = $props;

    return nav(
        ...array_map(
            fn($item) => a($item['text'])->href($item['href']),
            $items
        )
    )->class('nav');
}
```

### 3. 创建卡片组件

创建 `components/Card.php`：

```php
<?php

use function Pure\HTML\{div, h2, p};

function Card($props) {
    [
        'title' => $title,
        'content' => $content,
        'image' => $image = null
    ] = $props;

    return div(
        $image ? img()->src($image)->class('card-img-top') : null,
        div(
            h2($title)->class('card-title'),
            p($content)->class('card-text')
        )->class('card-body')
    )->class('card');
}
```

### 4. 使用组件

更新 `index.php`：

```php
<?php

require 'vendor/autoload.php';
require 'components/Navigation.php';
require 'components/Card.php';

use function Pure\HTML\{div, h1, p, link};

// 添加样式表
link()
    ->rel('stylesheet')
    ->href('style.css')
    ->toPrint();

// 创建页面布局
div(
    // 头部
    div(
        h1('我的 PurePHP 应用'),
        Navigation([
            'items' => [
                ['text' => '首页', 'href' => '/'],
                ['text' => '关于', 'href' => '/about'],
                ['text' => '联系', 'href' => '/contact']
            ]
        ])
    )->class('header'),

    // 主要内容
    div(
        Card([
            'title' => '欢迎',
            'content' => '这是一个使用 PurePHP 创建的示例页面。',
            'image' => 'welcome.jpg'
        ]),
        Card([
            'title' => '特性',
            'content' => 'PurePHP 提供了许多强大的特性，帮助你构建现代化的 Web 应用。'
        ])
    )->class('main'),

    // 底部
    div(
        p('© 2024 我的 PurePHP 应用')
    )->class('footer')
)->class('app')->toPrint();
```

## 添加交互

### 1. 创建计数器组件

创建 `components/Counter.php`：

```php
<?php

use Pure\Core\{HTML, render};

class Counter extends HTML {
    private $count = 0;

    public function __construct($props = [], $children = []) {
        parent::__construct('div', $props, $children);
    }

    public function increment() {
        $this->count++;
        $this->render();
    }

    public function render() {
        return new HTML('div', ['class' => 'counter'], [
            new HTML('h3', [], ["计数: {$this->count}"]),
            new HTML('button', [
                'onclick' => "counter.increment()"
            ], ['增加'])
        ]);
    }
}
```

### 2. 使用计数器组件

更新 `index.php`：

```php
<?php

require 'vendor/autoload.php';
require 'components/Navigation.php';
require 'components/Card.php';
require 'components/Counter.php';

use function Pure\HTML\{div, h1, p, link, script};
use Pure\Core\render;

// 添加样式表
link()
    ->rel('stylesheet')
    ->href('style.css')
    ->toPrint();

// 创建计数器实例
$counter = new Counter();

// 创建页面布局
div(
    // 头部
    div(
        h1('我的 PurePHP 应用'),
        Navigation([
            'items' => [
                ['text' => '首页', 'href' => '/'],
                ['text' => '关于', 'href' => '/about'],
                ['text' => '联系', 'href' => '/contact']
            ]
        ])
    )->class('header'),

    // 主要内容
    div(
        Card([
            'title' => '欢迎',
            'content' => '这是一个使用 PurePHP 创建的示例页面。',
            'image' => 'welcome.jpg'
        ]),
        Card([
            'title' => '特性',
            'content' => 'PurePHP 提供了许多强大的特性，帮助你构建现代化的 Web 应用。'
        ])
    )->class('main'),

    // 底部
    div(
        p('© 2024 我的 PurePHP 应用')
    )->class('footer'),

    div(
        render($counter)
    )->class('counter-section')
)->class('app')->toPrint();

// 添加 JavaScript
script("
    const counter = {
        increment() {
            fetch('/increment.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.counter h3').textContent = '计数: ' + data.count;
                });
        }
    };
")->toPrint();
```

### 3. 创建处理程序

创建 `increment.php`：

```php
<?php

require 'vendor/autoload.php';
require 'components/Counter.php';

use Pure\Core\render;

$counter = new Counter();
$counter->increment();

header('Content-Type: application/json');
echo json_encode(['count' => $counter->getCount()]);
```

## 下一步

- [安装](/guide/installation) - 了解如何安装和配置 PurePHP
- [基本概念](/guide/concepts) - 深入理解 PurePHP 的核心概念
- [组件](/guide/components) - 学习如何创建和使用组件

## 常见问题

### 1. 为什么选择 PurePHP？

PurePHP 提供了一个简单而强大的方式来构建 PHP Web 应用，它：
- 使用纯 PHP 代码，无需学习新的模板语法
- 支持组件化开发，提高代码复用性
- 提供类 HTML 的语法，降低学习成本

### 2. 性能如何？

PurePHP 使用虚拟 DOM 实现，可以高效地处理 DOM 更新，同时保持了较小的运行时开销。

### 3. 是否需要其他依赖？

PurePHP 是零依赖的，只需要 PHP 7.4+ 和 Composer 即可使用。
