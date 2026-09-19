# PurePHP 与 TailwindCSS 集成

PurePHP 与 TailwindCSS 的结合为你提供了强大的开发体验：组件化的 PHP 模板引擎配合实用优先的 CSS 框架。

*本指南中的可复用组件都是函数组件：静态 Tailwind 类字符串在构建时只写一次，每次请求的值通过槽位传入。参见[组件](/zh/guide/components)与[编译组件](/zh/guide/compiled)。即时标签 API（`render()` / `print()`）仍然可用于代码片段，Tailwind 在两条路径中都能找到类名，因为它们始终位于 PHP 源码中。*

## 为什么选择这个组合？

- **PurePHP**：提供组件化的 PHP 模板渲染
- **TailwindCSS**：提供实用优先的 CSS 类系统
- **完美互补**：PurePHP 处理结构和逻辑，TailwindCSS 处理样式

## 快速开始

### 1. 安装依赖

首先安装 PurePHP：

```bash
composer require yonld/purephp
```

然后安装 TailwindCSS：

```bash
npm install -D tailwindcss
npx tailwindcss init
```

### 2. 配置 TailwindCSS

在 `tailwind.config.js` 中配置内容路径：

```javascript
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.php",
    "./public/**/*.php",
    "./components/**/*.php",
    "./views/**/*.php"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

### 3. 创建 CSS 文件

创建 `src/input.css`：

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### 4. 构建 CSS

```bash
npx tailwindcss -i ./src/input.css -o ./public/output.css --watch
```

## 基础用法

### 简单组件

变体决定静态类列表，因此每种变体各自记忆化一个 renderer；标题与内容是动态的，成为槽位：

```php
<?php

use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\bind;
use function Pure\HTML\{div, h1, p};

function Card(string $title, string $content, string $variant = 'default'): Raw
{
    static $renders = [];

    $baseClasses = 'rounded-lg shadow-md p-6 bg-white';
    $variantClasses = match($variant) {
        'primary' => 'border-l-4 border-blue-500',
        'success' => 'border-l-4 border-green-500',
        'warning' => 'border-l-4 border-yellow-500',
        'danger' => 'border-l-4 border-red-500',
        default => 'border border-gray-200'
    };

    $render = $renders[$variant] ??= bind(
        div(
            h1(Slot::text('title'))->class('text-xl font-bold text-gray-900 mb-2'),
            p(Slot::text('content'))->class('text-gray-600 leading-relaxed')
        )->class("{$baseClasses} {$variantClasses}")
    );

    return $render([
        'title' => $title,
        'content' => $content,
    ]);
}

// 使用组件
echo Card('Welcome to PurePHP', 'This is a card component styled with TailwindCSS', 'primary');
```

### 响应式布局

网格没有自己的数据；它用 `ProjectCard()` 渲染每一项，并把拼接后的标记经 `Slot::raw()` 注入：

```php
<?php

use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\bind;
use function Pure\HTML\{div, h2, p, img};

function ProjectCard(string $title, string $description, string $image): Raw
{
    static $render;
    $render ??= bind(
        div(
            img()->src(Slot::attr('image'))->alt(Slot::attr('title'))
                ->class('w-full h-48 object-cover rounded-t-lg'),
            div(
                h2(Slot::text('title'))->class('text-lg font-semibold mb-2'),
                p(Slot::text('description'))->class('text-gray-600 text-sm')
            )->class('p-4')
        )->class('bg-white rounded-lg shadow-md overflow-hidden')
    );

    return $render(['title' => $title, 'description' => $description, 'image' => $image]);
}

function ResponsiveGrid(array $items): Raw
{
    static $render;
    $render ??= bind(
        div(Slot::raw('items'))
            ->class('grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6')
    );

    $cards = [];

    foreach ($items as $item) {
        $cards[] = (string)ProjectCard($item['title'], $item['description'], $item['image']);
    }

    return $render(['items' => implode('', $cards)]);
}

// 使用响应式网格
echo ResponsiveGrid([
    ['title' => 'Project 1', 'description' => 'Description 1', 'image' => 'image1.jpg'],
    ['title' => 'Project 2', 'description' => 'Description 2', 'image' => 'image2.jpg'],
    ['title' => 'Project 3', 'description' => 'Description 3', 'image' => 'image3.jpg'],
]);
```

### 表单组件

字段配置作为参数传入；错误消息与错误状态的输入类按请求绑定（`Slot::if()` 仅在数据提供错误时才渲染错误行）：

```php
<?php

use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\bind;
use function Pure\HTML\{form, div, label, input, button, span};

const INPUT_CLASS = 'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 border-gray-300';

function FormField(
    string $labelText,
    string $name,
    string $type = 'text',
    string $placeholder = '',
    bool $required = false,
    string $inputClass = INPUT_CLASS,
    string $error = ''
): Raw {
    static $renders = [];

    $key = "{$labelText}|{$name}|{$type}|{$placeholder}|" . (int)$required;

    $render = $renders[$key] ??= bind(
        div(
            label($labelText)
                ->for($name)
                ->class('block text-sm font-medium text-gray-700 mb-1'),
            input()
                ->type($type)
                ->name($name)
                ->id($name)
                ->placeholder($placeholder)
                ->required($required)
                ->class(Slot::attr('inputClass')),
            Slot::if('error', span(Slot::text('error'))->class('text-red-500 text-sm mt-1'))
        )->class('mb-4')
    );

    return $render(['inputClass' => $inputClass, 'error' => $error]);
}

function ContactForm(array $fields): Raw
{
    static $render;
    $render ??= bind(
        form(
            Slot::raw('fields'),
            button('Submit')
                ->type('submit')
                ->class('w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200')
        )->class('max-w-md mx-auto bg-white p-6 rounded-lg shadow-md')
    );

    $html = '';

    foreach ($fields as $field) {
        $html .= (string)FormField(
            $field['label'],
            $field['name'],
            $field['type'] ?? 'text',
            $field['placeholder'] ?? '',
            $field['required'] ?? false,
            $field['inputClass'] ?? INPUT_CLASS,
            $field['error'] ?? '',
        );
    }

    return $render(['fields' => $html]);
}

// 只有出错的字段覆盖默认输入类
echo ContactForm([
    ['label' => 'Name', 'name' => 'name', 'placeholder' => 'Enter your name', 'required' => true],
    [
        'label' => 'Email',
        'name' => 'email',
        'type' => 'email',
        'placeholder' => 'Enter your email',
        'required' => true,
        'error' => 'Enter a valid email address',
        'inputClass' => 'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 border-red-500',
    ],
    ['label' => 'Message', 'name' => 'message', 'type' => 'textarea', 'placeholder' => 'Enter your message'],
]);
```

## 高级用法

### 动态类名

变体、尺寸和状态决定静态类列表，因此每种组合各自记忆化一个 renderer。只有标签是动态的：

```php
<?php

use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\bind;
use function Pure\HTML\button;

function ActionButton(
    string $text,
    string $variant = 'primary',
    string $size = 'md',
    bool $disabled = false,
    bool $fullWidth = false
): Raw {
    static $renders = [];

    $baseClasses = 'font-medium rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

    $variantClasses = match($variant) {
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-blue-500',
        default => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
    };

    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg',
        default => 'px-4 py-2 text-base'
    };

    $widthClasses = $fullWidth ? 'w-full' : '';
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';

    $allClasses = trim("{$baseClasses} {$variantClasses} {$sizeClasses} {$widthClasses} {$disabledClasses}");

    $key = "{$variant}|{$size}|" . (int)$disabled . (int)$fullWidth;

    $render = $renders[$key] ??= bind(
        button(Slot::text('text'))
            ->class($allClasses)
            ->disabled($disabled)
    );

    return $render(['text' => $text]);
}

// 使用动态按钮
echo ActionButton('Primary Button', 'primary', 'lg');
```

### 主题切换

主题决定静态类列表，因此每种主题各自记忆化一个 renderer；切换按钮与页面作为已渲染的组件传入，经 `Slot::raw()` 注入：

```php
<?php

use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\bind;
use function Pure\HTML\{div, main, h1, button};

function Page(string $title): Raw
{
    static $render;
    $render ??= bind(
        main(h1(Slot::text('title')))->class('container mx-auto p-6')
    );

    return $render(['title' => $title]);
}

function ThemeToggle(string $currentTheme = 'light'): Raw
{
    static $renders = [];

    $newTheme = $currentTheme === 'light' ? 'dark' : 'light';
    $icon = $currentTheme === 'light' ? '🌙' : '☀️';

    $render = $renders[$currentTheme] ??= bind(
        button("{$icon} Toggle Theme")
            ->onclick("toggleTheme('{$newTheme}')")
            ->class('fixed top-4 right-4 px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600')
    );

    return $render([]);
}

function ThemeProvider(string $theme, Raw $toggle, Raw $page): Raw
{
    static $renders = [];

    $themeClasses = match($theme) {
        'dark' => 'bg-gray-900 text-white',
        'light' => 'bg-white text-gray-900',
        default => 'bg-white text-gray-900'
    };

    $render = $renders[$theme] ??= bind(
        div(
            Slot::raw('toggle'),
            Slot::raw('page')
        )->class("min-h-screen {$themeClasses}")
    );

    return $render(['toggle' => $toggle, 'page' => $page]);
}

// 为当前主题渲染提供者
echo ThemeProvider('dark', ThemeToggle('dark'), Page('Dashboard'));
```

## 工具函数

### 类名合并工具

```php
<?php

function clsx(...$classes) {
    $result = [];

    foreach ($classes as $class) {
        if (is_string($class) && !empty(trim($class))) {
            $result[] = trim($class);
        } elseif (is_array($class)) {
            foreach ($class as $key => $value) {
                if (is_numeric($key) && is_string($value)) {
                    $result[] = trim($value);
                } elseif (is_string($key) && $value) {
                    $result[] = trim($key);
                }
            }
        }
    }

    return implode(' ', array_unique(array_filter($result)));
}

// 使用示例
$isActive = true;
$hasError = false;

$classes = clsx(
    'base-class',
    'another-class',
    [
        'active' => $isActive,
        'error' => $hasError,
        'text-red-500' => $hasError
    ]
);

echo $classes; // 输出: base-class another-class active
```


## 下一步

- [TailwindCSS 文档](https://tailwindcss.com/docs)
- [PurePHP 组件指南](/zh/guide/components)
- [PurePHP 工具函数](/zh/guide/utils)
