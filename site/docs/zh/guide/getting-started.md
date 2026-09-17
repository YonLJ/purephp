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
)->print();
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

创建 `index.php` 入口文件：一个页面单元（注册的模板加页面函数）及其输出：

```php
<?php

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{registerPage, renderPage};
use function Pure\HTML\{div, h1, p};

registerPage('Page', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h1(Slot::text('heading')),
        p(Slot::text('lead')),
        p(Slot::text('body'))
    )->class('container')
));

function pageView(array $data): Raw
{
    return renderPage('Page', [
        'heading' => $data['heading'],
        'lead' => $data['lead'],
        'body' => $data['body'],
    ]);
}

echo pageView([
    'heading' => 'My First PurePHP Application',
    'lead' => 'Welcome to PurePHP!',
    'body' => 'This is a simple yet powerful PHP template engine.',
]);
```

`renderPage()` 每进程只加载一次模板并附加文档声明。标准 PHP-FPM 下请启用
`Compile::cachePath()`，让请求加载已编译的渲染器而不是重新构建；或者用
`vendor/bin/pure compile .` 预编译，让绑定器直接加载产物。

### 3. 运行应用

在浏览器中打开 `index.php`，或使用 PHP 内置服务器：

```bash
php -S localhost:8000
```

然后访问 `http://localhost:8000`，查看你的第一个 PurePHP 应用！

### 4. 启用开发期 guard

开发期间请启用 guard，让每个请求都重建形状的问题被报告出来，而不是悄悄拖慢页面：

```php
// index.php，首次渲染之前
Compile::guard(true);           // 或设置 PURE_COMPILE_GUARD=1
```

当同一调用点在单个进程内过多地调用 `Compile::shape()` 时，它会按调用点发出一次
`E_USER_WARNING`；例如每次调用都重建的内联 `component(...)`。文件形式的组件走
`render()`，绑定器按模板路径缓存，不会反复编译。

## 基础示例

### 使用组件

组件是一个 `*.cmp.php` 单元：带类型化参数、返回 `Raw` 的函数，加上紧挨着注册的惰性模板工厂：

```php
<?php

// Card.cmp.php

require 'vendor/autoload.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h2(Slot::text('title')),
        p(Slot::text('content'))
    )->class(Slot::attr('class'))
));

function Card(string $title, string $content, string $class = 'card'): Raw
{
    return render('Card', title: $title, content: $content, class: $class);
}

// 使用数据渲染组件
echo Card('Card Title', 'This is the card content');
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

对于代码片段——即立即渲染的小片段——你可以继续使用标签 API 与 `print()`：

```php
<?php

use function Pure\HTML\div;

div('Content')
    ->class('container')
    ->style('background: #f0f0f0; padding: 20px;')
    ->data_id('main-content')
    ->print();
```

## 下一步

- [编译组件](/zh/guide/compiled) - 组件、列表、条件与缓存
- [基本概念](/zh/guide/concepts) - 理解 PurePHP 的基础知识
- [Props 与槽位](/zh/guide/props) - 学习数据如何绑定到形状
