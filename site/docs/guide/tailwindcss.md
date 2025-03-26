# PurePHP 与 TailwindCSS 集成

PurePHP 与 TailwindCSS 的结合为你提供了一个强大的开发体验：组件化的 PHP 模板引擎配合实用优先的 CSS 框架。

## 为什么选择这个组合？

- **PurePHP**: 提供组件化的 PHP 模板渲染
- **TailwindCSS**: 提供实用优先的 CSS 类系统
- **完美互补**: PurePHP 处理结构和逻辑，TailwindCSS 处理样式

## 快速开始

### 1. 安装依赖

首先安装 PurePHP：

```bash
composer require yonlj/purephp
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

```php
<?php

use function Pure\HTML\{div, h1, p, button};

function Card($props) {
    [
        'title' => $title,
        'content' => $content,
        'variant' => $variant = 'default'
    ] = $props;

    $baseClasses = 'rounded-lg shadow-md p-6 bg-white';
    $variantClasses = match($variant) {
        'primary' => 'border-l-4 border-blue-500',
        'success' => 'border-l-4 border-green-500',
        'warning' => 'border-l-4 border-yellow-500',
        'danger' => 'border-l-4 border-red-500',
        default => 'border border-gray-200'
    };

    return div(
        h1($title)->class('text-xl font-bold text-gray-900 mb-2'),
        p($content)->class('text-gray-600 leading-relaxed')
    )->class("{$baseClasses} {$variantClasses}");
}

// 使用组件
Card([
    'title' => '欢迎使用 PurePHP',
    'content' => '这是一个使用 TailwindCSS 样式的卡片组件',
    'variant' => 'primary'
])->toPrint();
```

### 响应式布局

```php
<?php

use function Pure\HTML\{div, h2, p, img};

function ResponsiveGrid($items) {
    return div(
        ...array_map(function($item) {
            return div(
                img()->src($item['image'])->alt($item['title'])
                    ->class('w-full h-48 object-cover rounded-t-lg'),
                div(
                    h2($item['title'])->class('text-lg font-semibold mb-2'),
                    p($item['description'])->class('text-gray-600 text-sm')
                )->class('p-4')
            )->class('bg-white rounded-lg shadow-md overflow-hidden');
        }, $items)
    )->class('grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6');
}

// 使用响应式网格
$items = [
    ['title' => '项目 1', 'description' => '描述 1', 'image' => 'image1.jpg'],
    ['title' => '项目 2', 'description' => '描述 2', 'image' => 'image2.jpg'],
    ['title' => '项目 3', 'description' => '描述 3', 'image' => 'image3.jpg'],
];

ResponsiveGrid($items)->toPrint();
```

### 表单组件

```php
<?php

use function Pure\HTML\{form, div, label, input, button, span};

function FormField($props) {
    [
        'label' => $labelText,
        'type' => $type = 'text',
        'name' => $name,
        'placeholder' => $placeholder = '',
        'required' => $required = false,
        'error' => $error = null
    ] = $props;

    return div(
        label($labelText)
            ->for($name)
            ->class('block text-sm font-medium text-gray-700 mb-1'),
        input()
            ->type($type)
            ->name($name)
            ->id($name)
            ->placeholder($placeholder)
            ->required($required)
            ->class(
                'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500' .
                ($error ? ' border-red-500' : ' border-gray-300')
            ),
        $error ? span($error)->class('text-red-500 text-sm mt-1') : null
    )->class('mb-4');
}

function ContactForm() {
    return form(
        FormField([
            'label' => '姓名',
            'name' => 'name',
            'placeholder' => '请输入您的姓名',
            'required' => true
        ]),
        FormField([
            'label' => '邮箱',
            'type' => 'email',
            'name' => 'email',
            'placeholder' => '请输入您的邮箱',
            'required' => true
        ]),
        FormField([
            'label' => '消息',
            'type' => 'textarea',
            'name' => 'message',
            'placeholder' => '请输入您的消息'
        ]),
        button('提交')
            ->type('submit')
            ->class('w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200')
    )->class('max-w-md mx-auto bg-white p-6 rounded-lg shadow-md');
}

ContactForm()->toPrint();
```

## 高级用法

### 动态类名

```php
<?php

use function Pure\HTML\button;

function Button($props) {
    [
        'text' => $text,
        'variant' => $variant = 'primary',
        'size' => $size = 'md',
        'disabled' => $disabled = false,
        'fullWidth' => $fullWidth = false
    ] = $props;

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

    return button($text)
        ->class($allClasses)
        ->disabled($disabled);
}

// 使用动态按钮
Button([
    'text' => '主要按钮',
    'variant' => 'primary',
    'size' => 'lg'
])->toPrint();
```

### 主题切换

```php
<?php

use function Pure\HTML\{div, button};

function ThemeProvider($props) {
    [
        'children' => $children,
        'theme' => $theme = 'light'
    ] = $props;

    $themeClasses = match($theme) {
        'dark' => 'bg-gray-900 text-white',
        'light' => 'bg-white text-gray-900',
        default => 'bg-white text-gray-900'
    };

    return div(...$children)->class("min-h-screen {$themeClasses}");
}

function ThemeToggle($currentTheme) {
    $newTheme = $currentTheme === 'light' ? 'dark' : 'light';
    $icon = $currentTheme === 'light' ? '🌙' : '☀️';

    return button("{$icon} 切换主题")
        ->onclick("toggleTheme('{$newTheme}')")
        ->class('fixed top-4 right-4 px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600');
}
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
- [PurePHP 组件指南](/guide/components)
- [PurePHP 工具函数](/guide/utils)
