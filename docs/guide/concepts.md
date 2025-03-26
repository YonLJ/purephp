# 基本概念

本指南将介绍 PurePHP 的核心概念。

## 虚拟 DOM

虚拟 DOM 是 PurePHP 的核心概念之一。它是一个轻量级的 JavaScript 对象，用于描述实际的 DOM 结构。

### 工作原理

1. **创建虚拟 DOM**
   ```php
   <?php

   use Pure\Core\{HTML, render};

   // 创建虚拟 DOM 节点
   $node = new HTML('div', ['class' => 'container'], [
       new HTML('h1', [], ['标题']),
       new HTML('p', [], ['内容'])
   ]);
   ```

2. **渲染到实际 DOM**
   ```php
   // 渲染虚拟 DOM
   render($node);
   ```

3. **更新虚拟 DOM**
   ```php
   // 更新节点
   $node->class('new-container');
   render($node);
   ```

### 优势

- **性能优化**：只在必要时更新实际 DOM
- **跨平台**：可以在不同环境中渲染
- **易于测试**：可以方便地测试虚拟 DOM 结构

## 组件

组件是 PurePHP 中构建用户界面的基本单位。

### 函数组件

```php
<?php

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
    'title' => '标题',
    'content' => '内容'
])->toPrint();
```

### 类组件

```php
<?php

use Pure\Core\{HTML, render};

class Button extends HTML {
    public function __construct($props = [], $children = []) {
        parent::__construct('button', $props, $children);
    }

    public function primary() {
        return $this->class('btn btn-primary');
    }

    public function large() {
        return $this->class('btn btn-lg');
    }
}

// 使用组件
$button = new Button(['type' => 'submit'], ['点击我']);
$button->primary()->large();
render($button);
```

## 属性

属性用于配置组件的行为和外观。

### 基本属性

```php
<?php

use function Pure\HTML\{div, p};

div(
    p('内容')
)
->id('main')
->class('container')
->style('background: #fff;')
->toPrint();
```

### 事件属性

```php
<?php

use function Pure\HTML\{button};

button('点击我')
    ->onclick('handleClick()')
    ->onmouseover('handleHover()')
    ->toPrint();
```

### 数据属性

```php
<?php

use function Pure\HTML\{div};

div('内容')
    ->data_id('123')
    ->data_type('card')
    ->toPrint();
```

## 生命周期

PurePHP 组件具有以下生命周期方法：

### 构造函数

```php
class MyComponent extends HTML {
    public function __construct($props = [], $children = []) {
        parent::__construct('div', $props, $children);
        // 初始化代码
    }
}
```

### 渲染方法

```php
class MyComponent extends HTML {
    public function render() {
        return new HTML('div', ['class' => 'my-component'], [
            new HTML('h1', [], ['标题']),
            ...$this->children
        ]);
    }
}
```

### 更新方法

```php
class MyComponent extends HTML {
    public function update($newProps) {
        $this->props = array_merge($this->props, $newProps);
        $this->render();
    }
}
```

## 状态管理

PurePHP 提供了简单的状态管理机制。

### 组件状态

```php
<?php

use Pure\Core\{HTML, render};

class Counter extends HTML {
    private $count = 0;

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

$counter = new Counter();
render($counter);
```

### 全局状态

```php
<?php

class Store {
    private static $state = [];

    public static function set($key, $value) {
        self::$state[$key] = $value;
    }

    public static function get($key) {
        return self::$state[$key] ?? null;
    }
}

// 使用全局状态
Store::set('user', ['name' => '张三']);
$user = Store::get('user');
```

## 性能优化

### 1. 使用虚拟 DOM

```php
<?php

use Pure\Core\{HTML, render};

// 创建虚拟 DOM
$node = new HTML('div', ['class' => 'container'], [
    new HTML('h1', [], ['标题']),
    new HTML('p', [], ['内容'])
]);

// 只在必要时渲染
render($node);
```

### 2. 组件缓存

```php
<?php

class CachedComponent extends HTML {
    private $cachedRender = null;

    public function render() {
        if ($this->cachedRender === null) {
            $this->cachedRender = new HTML('div', [], [
                new HTML('h1', [], ['标题'])
            ]);
        }
        return $this->cachedRender;
    }
}
```

### 3. 延迟加载

```php
<?php

function LazyComponent($props) {
    [
        'load' => $load = false
    ] = $props;

    if (!$load) {
        return div('加载中...');
    }

    return div('实际内容');
}
```

## 最佳实践

### 1. 组件设计

- 保持组件单一职责
- 使用 props 传递数据
- 避免组件内部状态
- 提取可复用逻辑

### 2. 性能考虑

- 合理使用虚拟 DOM
- 避免不必要的渲染
- 使用组件缓存
- 实现延迟加载

### 3. 代码组织

- 按功能组织文件
- 使用命名空间
- 遵循 PSR 规范
- 编写单元测试

## 下一步

- [组件](/guide/components) - 深入学习组件开发
- [属性](/guide/props) - 了解属性系统
- [事件](/guide/events) - 学习事件处理
