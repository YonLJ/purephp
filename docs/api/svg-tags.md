# SVG 标签 API

PurePHP 提供了所有标准 SVG 标签的函数。每个函数都返回一个 SVG 对象，支持链式调用设置属性。

## 基本用法

```php
<?php

use function Pure\SVG\{svg, circle, rect};

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

// 创建复杂图形
svg(
    rect()
        ->x(10)
        ->y(10)
        ->width(80)
        ->height(80)
        ->fill('none')
        ->stroke('blue')
        ->stroke_width(2),
    circle()
        ->cx(50)
        ->cy(50)
        ->r(30)
        ->fill('green')
)->width(100)->height(100)->toPrint();
```

## 可用标签

### 基础图形

- `circle()` - 圆形
- `rect()` - 矩形
- `ellipse()` - 椭圆
- `line()` - 直线
- `polyline()` - 折线
- `polygon()` - 多边形
- `path()` - 路径

### 文本和图片

- `text()` - 文本
- `tspan()` - 文本片段
- `image()` - 图片
- `use()` - 重用元素

### 容器和组

- `g()` - 组
- `defs()` - 定义
- `symbol()` - 符号
- `mask()` - 遮罩
- `clipPath()` - 裁剪路径

### 渐变和滤镜

- `linearGradient()` - 线性渐变
- `radialGradient()` - 径向渐变
- `filter()` - 滤镜
- `feGaussianBlur()` - 高斯模糊
- `feColorMatrix()` - 颜色矩阵

## 属性设置

所有 SVG 标签都支持以下属性设置方法：

### 基本属性

- `id($value)` - 设置 ID
- `class($value)` - 设置类名
- `style($value)` - 设置样式
- `title($value)` - 设置标题

### 图形属性

- `fill($value)` - 设置填充颜色
- `stroke($value)` - 设置描边颜色
- `stroke_width($value)` - 设置描边宽度
- `stroke_linecap($value)` - 设置线帽样式
- `stroke_linejoin($value)` - 设置线连接样式
- `stroke_dasharray($value)` - 设置虚线样式
- `opacity($value)` - 设置透明度

### 变换属性

- `transform($value)` - 设置变换
- `translate($x, $y)` - 设置平移
- `scale($x, $y)` - 设置缩放
- `rotate($angle, $cx, $cy)` - 设置旋转
- `skewX($angle)` - 设置 X 轴倾斜
- `skewY($angle)` - 设置 Y 轴倾斜

### 路径属性

- `d($value)` - 设置路径数据
- `pathLength($value)` - 设置路径长度

### 文本属性

- `x($value)` - 设置 X 坐标
- `y($value)` - 设置 Y 坐标
- `dx($value)` - 设置 X 偏移
- `dy($value)` - 设置 Y 偏移
- `text_anchor($value)` - 设置文本锚点
- `dominant_baseline($value)` - 设置基线对齐

## 示例

### 创建简单图表

```php
<?php

use function Pure\SVG\{svg, rect, text};

function BarChart($data) {
    $bars = [];
    $max = max($data);
    $width = 400;
    $height = 200;
    $barWidth = $width / count($data);

    foreach ($data as $i => $value) {
        $x = $i * $barWidth;
        $barHeight = ($value / $max) * $height;
        $y = $height - $barHeight;

        $bars[] = rect()
            ->x($x)
            ->y($y)
            ->width($barWidth - 2)
            ->height($barHeight)
            ->fill('steelblue');

        $bars[] = text($value)
            ->x($x + $barWidth/2)
            ->y($y - 5)
            ->text_anchor('middle');
    }

    return svg(...$bars)
        ->width($width)
        ->height($height);
}

// 使用图表
BarChart([30, 50, 80, 40, 60])->toPrint();
```

### 创建动画效果

```php
<?php

use function Pure\SVG\{svg, circle, animate};

svg(
    circle()
        ->cx(50)
        ->cy(50)
        ->r(40)
        ->fill('red'),
    animate()
        ->attributeName('r')
        ->values('40;20;40')
        ->dur('2s')
        ->repeatCount('indefinite')
)->width(100)->height(100)->toPrint();
```

### 创建渐变效果

```php
<?php

use function Pure\SVG\{svg, defs, linearGradient, stop, rect};

svg(
    defs(
        linearGradient(
            stop()->offset('0%')->stop_color('red'),
            stop()->offset('100%')->stop_color('blue')
        )->id('gradient')
    ),
    rect()
        ->x(10)
        ->y(10)
        ->width(80)
        ->height(80)
        ->fill('url(#gradient)')
)->width(100)->height(100)->toPrint();
```
