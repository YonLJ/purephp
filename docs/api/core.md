# 核心 API

PurePHP 的核心 API 提供了基础的功能和工具，用于构建和管理虚拟 DOM。

## 基础类

### HTML 类

`Pure\Core\HTML` 类是所有 HTML 标签的基础类，提供了基本的属性和方法。

#### 构造函数

```php
new HTML(string $tag, array $props = [], array $children = [])
```

参数：
- `$tag`: 标签名称
- `$props`: 属性数组
- `$children`: 子元素数组

#### 方法

##### 属性设置

- `id($value)` - 设置 ID
- `class($value)` - 设置类名
- `style($value)` - 设置样式
- `title($value)` - 设置标题
- `lang($value)` - 设置语言
- `dir($value)` - 设置方向
- `data_*($value)` - 设置数据属性

##### 事件处理

- `onclick($value)` - 设置点击事件
- `onmouseover($value)` - 设置鼠标悬停事件
- `onmouseout($value)` - 设置鼠标移出事件
- `onkeydown($value)` - 设置按键按下事件
- `onkeyup($value)` - 设置按键释放事件
- `onsubmit($value)` - 设置表单提交事件

##### 渲染

- `toPrint()` - 打印 HTML
- `toString()` - 返回 HTML 字符串
- `toArray()` - 返回数组表示

### SVG 类

`Pure\Core\SVG` 类是所有 SVG 标签的基础类，提供了基本的属性和方法。

#### 构造函数

```php
new SVG(string $tag, array $props = [], array $children = [])
```

参数：
- `$tag`: 标签名称
- `$props`: 属性数组
- `$children`: 子元素数组

#### 方法

##### 属性设置

- `id($value)` - 设置 ID
- `class($value)` - 设置类名
- `style($value)` - 设置样式
- `title($value)` - 设置标题

##### 图形属性

- `fill($value)` - 设置填充颜色
- `stroke($value)` - 设置描边颜色
- `stroke_width($value)` - 设置描边宽度
- `stroke_linecap($value)` - 设置线帽样式
- `stroke_linejoin($value)` - 设置线连接样式
- `stroke_dasharray($value)` - 设置虚线样式
- `opacity($value)` - 设置透明度

##### 变换属性

- `transform($value)` - 设置变换
- `translate($x, $y)` - 设置平移
- `scale($x, $y)` - 设置缩放
- `rotate($angle, $cx, $cy)` - 设置旋转
- `skewX($angle)` - 设置 X 轴倾斜
- `skewY($angle)` - 设置 Y 轴倾斜

##### 渲染

- `toPrint()` - 打印 SVG
- `toString()` - 返回 SVG 字符串
- `toArray()` - 返回数组表示

## 工具函数

### 渲染函数

- `render($element)` - 渲染元素
- `renderToString($element)` - 渲染元素为字符串
- `renderToArray($element)` - 渲染元素为数组

### 属性处理

- `setProps($element, array $props)` - 设置元素属性
- `getProps($element)` - 获取元素属性
- `hasProp($element, string $name)` - 检查属性是否存在
- `getProp($element, string $name)` - 获取属性值

### 子元素处理

- `appendChild($parent, $child)` - 添加子元素
- `removeChild($parent, $child)` - 移除子元素
- `replaceChild($parent, $newChild, $oldChild)` - 替换子元素
- `getChildren($element)` - 获取子元素
- `hasChildren($element)` - 检查是否有子元素

## 示例

### 创建自定义组件

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

### 创建动态组件

```php
<?php

use Pure\Core\{HTML, render};

function Card($props, $children) {
    [
        'title' => $title,
        'image' => $image = null,
        'footer' => $footer = null
    ] = $props;

    return new HTML('div', ['class' => 'card'], [
        $image ? new HTML('img', ['src' => $image, 'class' => 'card-img-top']) : null,
        new HTML('div', ['class' => 'card-body'], [
            new HTML('h5', ['class' => 'card-title'], [$title]),
            ...$children,
            $footer ? new HTML('div', ['class' => 'card-footer'], [$footer]) : null
        ])
    ]);
}

// 使用组件
$card = Card(
    [
        'title' => '卡片标题',
        'image' => 'image.jpg',
        'footer' => '底部文本'
    ],
    [
        new HTML('p', ['class' => 'card-text'], ['卡片内容'])
    ]
);
render($card);
```

### 创建 SVG 组件

```php
<?php

use Pure\Core\{SVG, render};

class Icon extends SVG {
    public function __construct($props = [], $children = []) {
        parent::__construct('svg', $props, $children);
    }

    public function size($width, $height) {
        return $this->width($width)->height($height);
    }

    public function color($color) {
        return $this->fill($color);
    }
}

// 使用组件
$icon = new Icon(
    ['viewBox' => '0 0 24 24'],
    [
        new SVG('path', [
            'd' => 'M12 2L1 12h3v9h7v-6h2v6h7v-9h3L12 2z'
        ])
    ]
);
$icon->size(24, 24)->color('red');
render($icon);
```
