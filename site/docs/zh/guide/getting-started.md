# 快速开始

本指南将帮助你安装 PurePHP 并创建你的第一个应用。

## 环境要求

- PHP 8.1 或更高版本
- Composer

## 安装

### 使用 Composer

在你的项目目录中运行以下命令：

```bash
composer require yonld/purephp
```

### 验证安装

创建一个简单的测试文件 `test.php`：

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use function Pure\HTML\{div, h1, p};

div(
    h1('PurePHP Installation Successful'),
    p('Congratulations! PurePHP is correctly installed.')
)->toPrint();
```

运行测试文件：

```bash
php test.php
```

如果看到 HTML 输出，说明安装成功。注意这里使用的是即时渲染——它适合快速检查，但页面应当编译形状（见下文）。

## 创建第一个应用

### 1. 创建项目目录

```bash
mkdir my-purephp-app
cd my-purephp-app
composer require yonld/purephp
```

### 2. 创建入口文件

创建 `index.php`：

```php
<?php

require 'vendor/autoload.php';

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, p};

/**
 * 页面形状：把数据换成 Slot 占位符的无数据树。
 * 它在每个进程只构建一次（长驻 worker；标准 PHP-FPM 下请启用
 * Compile::cachePath()，让请求加载已编译的渲染器而不是重建）。
 */
function PageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h1(Slot::text('heading')),
            p(Slot::text('lead')),
            p(Slot::text('body'))
        )->class('container')
    );
}

PageShape()->print([
    'heading' => 'My First PurePHP Application',
    'lead' => 'Welcome to PurePHP!',
    'body' => 'This is a simple yet powerful PHP template engine.',
]);
```

### 3. 运行应用

在浏览器中打开 `index.php`，或使用 PHP 内置服务器：

```bash
php -S localhost:8000
```

然后访问 `http://localhost:8000`，查看你的第一个 PurePHP 应用！

## 基础示例

### 使用组件

组件是返回 `Shape` 的函数；静态 props 是函数参数，动态 props 是槽位：

```php
<?php

require 'vendor/autoload.php';

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, p};

function CardShape(string $classList = 'card'): Shape
{
    static $shapes = [];

    return $shapes[$classList] ??= Compile::shape(
        div(
            h2(Slot::text('title')),
            p(Slot::text('content'))
        )->class($classList)
    );
}

// 使用数据渲染组件
CardShape()->print([
    'title' => 'Card Title',
    'content' => 'This is the card content',
]);
```

### 设置属性

静态属性设置在形状上；动态属性使用 `Slot::attr()`：

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;

$shape = Compile::shape(
    div('Content')->class('container')->id(Slot::attr('id'))
);

$shape(['id' => 'main-content']);
```

对于代码片段——即立即渲染的小片段——你可以继续使用标签 API 与 `toPrint()`：

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->class('container')
    ->style('background: #f0f0f0; padding: 20px;')
    ->data_id('main-content')
    ->toPrint();
```

## 下一步

- [编译组件](/zh/guide/compiled) - 组件、列表、条件与缓存
- [基本概念](/zh/guide/concepts) - 理解 PurePHP 的基础知识
- [Props 与槽位](/zh/guide/props) - 学习数据如何绑定到形状
