# 基本概念

本指南将介绍 PurePHP 的核心概念。

## 对象到HTML转换

PurePHP 的核心是一个 PHP 对象到 HTML 字符串的转换系统。它使用 PHP 对象来表示 HTML 元素，然后将这些对象转换为 HTML 字符串。

### 工作原理

1. **创建 HTML 对象**
   ```php
   <?php

   use function Pure\HTML\{div, h1, p};

   // 创建 HTML 对象
   $element = div(
       h1('标题'),
       p('内容')
   )->class('container');
   ```

2. **转换为 HTML 字符串**
   ```php
   // 输出 HTML 字符串
   echo $element; // 或者 $element->toPrint();
   ```

3. **设置属性**
   ```php
   // 链式调用设置属性
   $element->class('new-container')->style('color: red;');
   ```

### 优势

- **类型安全**：利用 PHP 的类型系统提供更好的开发体验
- **组件化**：可以将 HTML 结构封装成可重用的 PHP 函数
- **易于测试**：可以方便地测试 HTML 结构和属性

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
    ->data_id('123')      // 对应 HTML 中的 data-id="123"
    ->data_type('card')   // 对应 HTML 中的 data-type="card"
    ->toPrint();
```

**重要提示**：由于 `-` 在 PHP 中有特殊含义，所有包含连字符的属性名都需要用下划线 `_` 替代。

## 状态管理

PurePHP 支持通过 PHP 变量和函数来管理状态。

### 简单状态

```php
<?php

use function Pure\HTML\{div, button, p};

function Counter($count = 0) {
    return div(
        p("计数: {$count}"),
        button('增加')
            ->onclick("increment()")
    );
}

// 使用状态
$currentCount = 0;
Counter($currentCount)->toPrint();
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



## 下一步

- [基本用法](/zh/guide/basic-usage) - 学习基础语法和用法
- [工具函数](/zh/guide/utils) - 了解内置的工具函数
- [组件](/zh/guide/components) - 深入学习组件开发
