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

### 2. 创建单元与入口文件

创建 `views/page.cmp.php`，即组件单元——注册的模板、`prepare()` 钩子里的类型化 prop
契约，加上调用函数：

```php
<?php

// views/page.cmp.php
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{div, h1, p};

register('Page', __FILE__,
    factory: static fn () =>
        div(
            h1(Slot::value('heading')),
            p(Slot::value('lead')),
            p(Slot::value('body'))
        )->class('container'),
    prepare: static function (string $heading, string $lead, string $body): array {
        return ['heading' => $heading, 'lead' => $lead, 'body' => $body];
    }
);

function Page(mixed ...$children): Call
{
    return component('Page', ...$children);
}
```

再创建 `index.php`，即入口文件，它加载单元并渲染：

```php
<?php

// index.php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/views/page.cmp.php';

use function Pure\Utils\renderHTML;

echo renderHTML(Page()
    ->heading('My First PurePHP Application')
    ->lead('Welcome to PurePHP!')
    ->body('This is a simple yet powerful PHP template engine.'));
```

单元必须有自己的 `*.cmp.php` 文件——`pure compile` 只发现这类文件，注册名解析到的正是
注册它的文件。`component()` 通过按名字或路径缓存的绑定器解析单元，模板每进程只加载一次；
文档声明由调用方
拼接。标准 PHP-FPM 下请启用 `Compile::cachePath()`，让请求加载已编译的渲染器而不是重新构建；
或者用 `vendor/bin/pure compile .` 预编译，让绑定器直接加载产物。

### 3. 运行应用

在浏览器中打开 `index.php`，或使用 PHP 内置服务器：

```bash
php -S localhost:8000
```

然后访问 `http://localhost:8000`，查看你的第一个 PurePHP 应用！

### 4. 启用开发期 guard

开发期间请启用 guard，让输出中看不出来的问题被报告出来，而不是悄悄拖慢页面或渲染为空：

```php
// index.php，首次渲染之前
Compile::guard(true);           // 或设置 PURE_COMPILE_GUARD=1
```

它在单个进程内每个对象只发出一次 `E_USER_WARNING`：

- 每请求重建形状：同一调用点在一个进程内第 20 次调用 `Compile::shape()` 时就会警告，
  例如每次调用都重建的内联 `Compile::shape(...)`。文件形式的组件走 `component()`，
  绑定器按名字或路径缓存，不会反复编译；
- 模板从未读取的 binding：会给出 `did you mean` 建议，因此拼错的键名（`titel`）
  不会被静默忽略；
- 与标准属性名只差一个字符的属性方法（`->clas(...)`、`->hreff(...)`），否则它们会
  静默变成没人注意的自定义属性。

## 基础示例

### 使用组件

组件是一个 `*.cmp.php` 单元：返回 `Pure\Component\Call` 的调用函数，紧挨着它渲染的模板，
以及放在 `prepare()` 钩子里的类型化 prop 契约：

```php
<?php

// Card.cmp.php

require 'vendor/autoload.php';

use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__,
    factory: static fn () =>
        div(
            h2(Slot::value('title')),
            p(Slot::value('content'))
        )->class(Slot::value('class')),
    prepare: static function (string $title, string $content, string $class = 'card'): array {
        return ['title' => $title, 'content' => $content, 'class' => $class];
    }
);

function Card(mixed ...$children): Call
{
    return component('Card', ...$children);
}

// 用数据渲染组件
echo Card()->title('Card Title')->content('This is the card content');
```

### 设置属性

静态属性设置在形状上；动态属性使用 `Slot::value()`：

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;

$shape = Compile::shape(
    div('Content')->class('container')->id(Slot::value('id'))
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
