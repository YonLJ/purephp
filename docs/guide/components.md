# 组件

组件是 PurePHP 中构建用户界面的基本单位。本指南将介绍如何创建和使用组件。

## 组件类型

PurePHP 支持两种类型的组件：

### 1. 函数组件

函数组件是最简单的组件形式，适合创建简单的、无状态的 UI 元素：

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

### 2. 类组件

类组件适合创建复杂的、有状态的组件：

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
        return new HTML('div', [], [
            new HTML('h3', [], ["计数: {$this->count}"]),
            new HTML('button', [
                'onclick' => "counter.increment()"
            ], ['增加'])
        ]);
    }
}

// 使用组件
$counter = new Counter();
render($counter);
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

## 组件生命周期

### 1. 构造函数

```php
class MyComponent extends HTML {
    public function __construct($props = [], $children = []) {
        parent::__construct('div', $props, $children);
        // 初始化代码
        $this->initialize();
    }

    private function initialize() {
        // 设置默认值
        $this->props['theme'] = $this->props['theme'] ?? 'light';
        $this->props['size'] = $this->props['size'] ?? 'medium';
    }
}
```

### 2. 渲染方法

```php
class MyComponent extends HTML {
    public function render() {
        // 渲染前的准备工作
        $this->prepareRender();

        // 返回渲染结果
        return new HTML('div', [
            'class' => 'my-component',
            'data-theme' => $this->props['theme']
        ], [
            new HTML('h1', [], [$this->props['title']]),
            ...$this->children
        ]);
    }

    private function prepareRender() {
        // 处理数据
        $this->processData();
        // 验证属性
        $this->validateProps();
    }
}
```

### 3. 更新方法

```php
class MyComponent extends HTML {
    public function update($newProps) {
        // 合并属性
        $this->props = array_merge($this->props, $newProps);

        // 验证新属性
        $this->validateProps();

        // 重新渲染
        $this->render();
    }

    private function validateProps() {
        // 验证属性
        if (!isset($this->props['title'])) {
            throw new \InvalidArgumentException('title 属性是必需的');
        }
    }
}
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

### 2. 事件通信

```php
<?php

use Pure\Core\{HTML, render};

class EventComponent extends HTML {
    private $listeners = [];

    public function on($event, $callback) {
        $this->listeners[$event][] = $callback;
        return $this;
    }

    public function emit($event, $data) {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $callback) {
                $callback($data);
            }
        }
    }

    public function render() {
        return new HTML('div', [
            'class' => 'event-component',
            'onclick' => 'component.emit("click", event)'
        ], [
            new HTML('button', [], ['触发事件'])
        ]);
    }
}
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

## 最佳实践

### 1. 组件设计原则

- 单一职责：每个组件只做一件事
- 可复用性：设计可重用的组件
- 可测试性：组件应该易于测试
- 可维护性：保持代码清晰和文档完整

### 2. 性能优化

- 使用组件缓存
- 避免不必要的渲染
- 合理使用虚拟 DOM
- 实现延迟加载

### 3. 代码组织

- 按功能组织文件
- 使用命名空间
- 遵循 PSR 规范
- 编写单元测试

## 下一步

- [属性](/guide/props) - 了解属性系统
- [事件](/guide/events) - 学习事件处理
- [性能优化](/guide/performance) - 优化组件性能
