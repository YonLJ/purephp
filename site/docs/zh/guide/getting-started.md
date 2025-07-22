# 快速开始

本指南将帮助你安装 PurePHP 并创建你的第一个应用。

## 环境要求

- PHP 8.1 或更高版本
- Composer

## 安装

### 使用 Composer 安装

在你的项目目录中运行：

```bash
composer require yonlj/purephp
```

### 验证安装

创建一个简单的测试文件 `test.php`：

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use function Pure\HTML\{div, h1, p};

div(
    h1('PurePHP 安装成功'),
    p('恭喜！PurePHP 已经正确安装。')
)->toPrint();
```

运行测试文件：

```bash
php test.php
```

如果看到输出的 HTML，说明安装成功。

## 创建第一个应用

### 1. 创建项目目录

```bash
mkdir my-purephp-app
cd my-purephp-app
composer require yonlj/purephp
```

### 2. 创建入口文件

创建 `index.php` 文件：

```php
<?php

require 'vendor/autoload.php';

use function Pure\HTML\{div, h1, p};

div(
    h1('我的第一个 PurePHP 应用'),
    p('欢迎使用 PurePHP！'),
    p('这是一个简单而强大的 PHP 模板引擎。')
)->class('container')->toPrint();
```

### 3. 运行应用

在浏览器中打开 `index.php` 或使用 PHP 内置服务器：

```bash
php -S localhost:8000
```

然后访问 `http://localhost:8000`，你应该能看到你的第一个 PurePHP 应用！

## 基础示例

### 使用组件

创建一个简单的组件：

```php
<?php

require 'vendor/autoload.php';

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
    'content' => '这是卡片的内容'
])->toPrint();
```

### 属性设置

```php
<?php

use function Pure\HTML\div;

div('内容')
    ->class('container')
    ->style('background: #f0f0f0; padding: 20px;')
    ->data_id('main-content')
    ->toPrint();
```

## 下一步

- [基本概念](/zh/guide/concepts) - 深入理解 PurePHP 的核心概念
- [基本用法](/zh/guide/basic-usage) - 学习基础语法和用法
- [组件](/zh/guide/components) - 学习如何创建和使用组件
