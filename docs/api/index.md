# API 参考

PurePHP 提供了完整的 API 文档，帮助你更好地使用这个框架。

## 核心 API

- [核心类](/api/core) - 了解 PurePHP 的核心类和基础功能
- [HTML 标签](/api/html-tags) - 查看所有可用的 HTML 标签函数
- [SVG 标签](/api/svg-tags) - 查看所有可用的 SVG 标签函数

## 快速导航

### HTML 标签

```php
<?php

use function Pure\HTML\{div, p};

// 创建元素
div('内容')->toPrint();

// 设置属性
div('内容')
    ->class('container')
    ->style('background: #fff;')
    ->toPrint();

// 嵌套元素
div(
    p('第一段'),
    p('第二段')
)->class('content')->toPrint();
```

### SVG 标签

```php
<?php

use function Pure\SVG\{svg, circle};

// 创建 SVG 元素
svg(
    circle()
        ->cx(50)
        ->cy(50)
        ->r(40)
        ->fill('red')
        ->stroke('black')
        ->stroke_width(2)
)->width(100)->height(100)->toPrint();
```

### 核心类

```php
<?php

use Pure\Core\{HTML, render};

// 创建自定义组件
class Button extends HTML {
    public function __construct($props = [], $children = []) {
        parent::__construct('button', $props, $children);
    }

    public function primary() {
        return $this->class('btn btn-primary');
    }
}

// 使用组件
$button = new Button(['type' => 'submit'], ['点击我']);
$button->primary();
render($button);
```

## 最佳实践

1. **使用命名空间导入**
   ```php
   use function Pure\HTML\{div, p, span};
   use function Pure\SVG\{svg, circle, rect};
   ```

2. **链式调用属性**
   ```php
   div('内容')
       ->class('container')
       ->style('background: #fff;')
       ->id('main')
       ->toPrint();
   ```

3. **组件化开发**
   ```php
   function Card($props, $children) {
       [
           'title' => $title,
           'image' => $image = null
       ] = $props;

       return div(
           $image ? img()->src($image)->class('card-img-top') : null,
           div(
               h5($title)->class('card-title'),
               ...$children
           )->class('card-body')
       )->class('card');
   }
   ```

4. **条件渲染**
   ```php
   div(
       $isLoggedIn ? span('已登录') : a('登录')->href('/login'),
       $hasNotifications ? span('有通知')->class('badge') : null
   )->class('user-menu');
   ```

5. **列表渲染**
   ```php
   ul(
       ...array_map(
           fn($item) => li($item['name'])->key($item['id']),
           $items
       )
   )->class('item-list');
   ```

## 常见问题

### 1. 如何设置多个类名？

```php
div('内容')
    ->class('container')
    ->class('mt-4')
    ->class('bg-white')
    ->toPrint();
```

### 2. 如何设置内联样式？

```php
div('内容')
    ->style('background: #fff;')
    ->style('padding: 1rem;')
    ->style('margin: 0;')
    ->toPrint();
```

### 3. 如何添加事件处理器？

```php
button('点击我')
    ->onclick('handleClick()')
    ->onmouseover('handleHover()')
    ->toPrint();
```

### 4. 如何创建 SVG 图标？

```php
function Icon($props) {
    [
        'name' => $name,
        'size' => $size = 24,
        'color' => $color = 'currentColor'
    ] = $props;

    return svg(
        use()
            ->href("#icon-$name")
    )
    ->width($size)
    ->height($size)
    ->fill($color);
}
```

## 贡献指南

如果你发现文档中有任何问题或需要改进的地方，欢迎提交 Issue 或 Pull Request。

1. Fork 项目
2. 创建特性分支
3. 提交更改
4. 推送到分支
5. 创建 Pull Request

## 许可证

本文档采用 MIT 许可证。
