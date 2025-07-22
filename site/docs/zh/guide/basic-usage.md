# 基础用法

本指南将介绍 PurePHP 的核心概念和基本用法。

## 基本语法

### 1. 创建 HTML 元素

PurePHP 提供多种创建 HTML 元素的方式：

#### 函数方式（推荐用于预定义标签）

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

#### 魔术静态方法（适合自定义标签）

```php
<?php

use Pure\Core\HTML;

// 使用魔术方法创建自定义 HTML 元素
HTML::customTag('自定义内容')->class('custom')->toPrint();

// 非常适合 Web 组件或非标准标签
HTML::myComponent(
    HTML::header('组件头部'),
    HTML::content('组件主体')
)->data_component('my-component')->toPrint();
```

#### 构造函数方法（适合性能关键代码）

```php
<?php

use Pure\Core\HTML;

// 直接构造函数方式
(new HTML('div', ['Hello World']))->class('container')->toPrint();

// 对于大型文档有更好的性能
$elements = [];
for ($i = 0; $i < 1000; $i++) {
    $elements[] = new HTML('item', ["项目 $i"]);
}
(new HTML('list', $elements))->class('large-list')->toPrint();
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

## 选择正确的方式

### 何时使用每种方法

#### 使用函数（推荐大多数情况）
- **最适合**：标准 HTML 标签，日常开发
- **优点**：语法简洁，性能良好，可读性优秀
- **示例**：`div()`、`p()`、`span()` 等

#### 使用魔术静态方法
- **最适合**：自定义标签、Web 组件、动态标签名
- **优点**：适用于任何标签名，语法优雅
- **示例**：`HTML::customElement()`、`HTML::webComponent()`

#### 使用构造函数
- **最适合**：性能关键代码、库、大型文档
- **优点**：最大性能，明确的类型检查
- **示例**：`new HTML('tag')` 用于数千个元素

```php
<?php

use function Pure\HTML\div;
use Pure\Core\HTML;

// 函数方式 - 推荐用于标准标签
$standard = div('标准内容')->class('container');

// 魔术方法 - 适合自定义标签
$custom = HTML::myCustomTag('自定义内容')->data_component('special');

// 构造函数 - 最佳性能
$performant = new HTML('div', ['性能内容']);
```

## 重要用法说明

### 1. 字符串内容 vs 原始内容

⚠️ **重要**：当传递包含 HTML/XML 标签的字符串内容时，标签会被自动过滤以确保安全：

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// ❌ 字符串中的 HTML 标签会被过滤
div('<p>这会被过滤</p>')->toPrint();
// 输出: <div>这会被过滤</div>

// ✅ 使用 rawHtml 保留 HTML 内容
div(rawHtml('<p>这会被保留</p>'))->toPrint();
// 输出: <div><p>这会被保留</p></div>
```

**为什么这很重要：**
- **安全性**：防止用户输入的 XSS 攻击
- **可预测性**：确保行为一致
- **明确性**：强制对原始内容做出明确选择

**何时使用 rawHtml/rawXml：**
- 包含预格式化的 HTML/XML 内容
- 嵌入模板或外部内容
- 处理可信的 HTML/XML 字符串
- 包含 JavaScript 或 CSS 代码块

### 2. className 别名

由于 `class` 是 PHP 的关键字，PurePHP 提供了 `className` 作为别名：

```php
<?php

use function Pure\HTML\div;

// 两种写法都可以
div('内容')->class('container')->toPrint();
div('内容')->className('container')->toPrint();
```

### 2. 内置工具函数

`class` 方法内置了 `clx` 函数，`style` 方法内置了 `sty` 函数，可以处理数组和条件参数：

```php
<?php

use function Pure\HTML\div;

$isActive = true;
$isLarge = false;

// class 方法自动使用 clx 处理
div('内容')
    ->class('btn', $isActive ? 'active' : null, $isLarge ? 'large' : null)
    ->style(['color' => 'red', 'font-size' => '16px'])
    ->toPrint();

// 等同于手动使用工具函数
use function Pure\Utils\{clx, sty};

$classes = clx('btn', $isActive ? 'active' : null, $isLarge ? 'large' : null);
$styles = sty(['color' => 'red', 'font-size' => '16px']);

div('内容')
    ->class($classes)
    ->style($styles)
    ->toPrint();
```

### 3. 属性命名规则

由于 `-` 在 PHP 中有特殊含义，类似 `data-id` 这种属性需要改成 `data_id`：

```php
<?php

use function Pure\HTML\div;

div('内容')
    ->data_id('123')           // 对应 data-id="123"
    ->data_type('card')        // 对应 data-type="card"
    ->aria_label('按钮')       // 对应 aria-label="按钮"
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

## 下一步

- [SVG 和 XML 支持](/zh/guide/svg-xml) - 了解 SVG 图形和 XML 文档
- [工具函数](/zh/guide/utils) - 了解内置的工具函数
- [组件](/zh/guide/components) - 学习如何创建和使用组件
- [TailwindCSS 集成](/zh/guide/tailwindcss) - 了解如何与 TailwindCSS 配合使用
