# 组件

组件是 PurePHP 中构建用户界面的基本单位。本指南将介绍如何创建和使用组件。

## 函数组件

PurePHP 使用函数组件来构建用户界面。函数组件是简单的 PHP 函数，接收属性参数并返回 HTML 元素：

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

// 使用组件
Card([
    'title' => '标题',
    'content' => '内容',
    'image' => 'image.jpg'
])->toPrint();
```



## 组件属性

### 1. 基本属性

```php
<?php

use function Pure\HTML\{div};

function Box($props) {
    [
        'width' => $width = '100%',
        'height' => $height = '100px',
        'color' => $color = '#000'
    ] = $props;

    return div()
        ->style("width: {$width}; height: {$height}; background: {$color};");
}

// 使用组件
Box([
    'width' => '200px',
    'height' => '150px',
    'color' => '#ff0000'
])->toPrint();
```

### 2. 事件属性

```php
<?php

use function Pure\HTML\{button};

function ActionButton($props) {
    [
        'text' => $text,
        'onClick' => $onClick,
        'disabled' => $disabled = false
    ] = $props;

    return button($text)
        ->onclick($onClick)
        ->disabled($disabled)
        ->class('action-button');
}

// 使用组件
ActionButton([
    'text' => '提交',
    'onClick' => 'handleSubmit()',
    'disabled' => false
])->toPrint();
```

### 3. 子组件

```php
<?php

use function Pure\HTML\{div, h1};

function Layout($props) {
    [
        'header' => $header,
        'content' => $content,
        'footer' => $footer
    ] = $props;

    return div(
        div($header)->class('header'),
        div($content)->class('content'),
        div($footer)->class('footer')
    )->class('layout');
}

// 使用组件
Layout([
    'header' => h1('标题'),
    'content' => '主要内容',
    'footer' => '页脚'
])->toPrint();
```



## 组件通信

### 1. 属性传递

```php
<?php

use function Pure\HTML\{div, button};

function ParentComponent() {
    return div(
        ChildComponent([
            'message' => '来自父组件的消息',
            'onAction' => 'handleChildAction'
        ])
    );
}

function ChildComponent($props) {
    [
        'message' => $message,
        'onAction' => $onAction
    ] = $props;

    return div(
        p($message),
        button('触发动作')
            ->onclick($onAction)
    );
}
```

### 2. 事件处理

```php
<?php

use function Pure\HTML\{div, button};

function EventButton($props) {
    [
        'text' => $text,
        'onClick' => $onClick
    ] = $props;

    return button($text)->onclick($onClick);
}

// 使用事件组件
EventButton([
    'text' => '点击我',
    'onClick' => 'alert("按钮被点击了!")'
])->toPrint();
```

## 组件复用

### 1. 高阶组件

```php
<?php

function withLoading($Component) {
    return function($props) use ($Component) {
        [
            'loading' => $loading = false,
            'error' => $error = null,
            ...$rest
        ] = $props;

        if ($loading) {
            return div('加载中...')->class('loading');
        }

        if ($error) {
            return div($error)->class('error');
        }

        return $Component($rest);
    };
}

// 使用高阶组件
$LoadingCard = withLoading('Card');
$LoadingCard([
    'loading' => true,
    'title' => '标题',
    'content' => '内容'
])->toPrint();
```

### 2. 组件组合

```php
<?php

function Page($props) {
    [
        'header' => $header,
        'sidebar' => $sidebar,
        'content' => $content,
        'footer' => $footer
    ] = $props;

    return div(
        Header($header),
        div(
            Sidebar($sidebar),
            MainContent($content)
        )->class('main-container'),
        Footer($footer)
    )->class('page');
}

// 使用组合组件
Page([
    'header' => ['title' => '页面标题'],
    'sidebar' => ['items' => ['菜单项1', '菜单项2']],
    'content' => ['title' => '主要内容'],
    'footer' => ['copyright' => '© 2024']
])->toPrint();
```

## 下一步

- [属性](/zh/guide/props) - 了解属性系统
- [事件](/zh/guide/events) - 学习事件处理
- [TailwindCSS 集成](/zh/guide/tailwindcss) - 了解如何与 TailwindCSS 配合使用
