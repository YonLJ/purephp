# API 参考

本文档介绍 PurePHP 核心类的公共方法。

## Tag 类

`Pure\Core\Tag` 是所有 HTML 和 SVG 标签的基础抽象类。

### 属性方法

#### `class(string|array|null ...$args): self`

设置元素的 CSS 类名，内置 `clx` 函数处理多个参数。

```php
<?php

use function Pure\HTML\div;

// 单个类名
div('内容')->class('container');

// 多个类名
div('内容')->class('btn', 'btn-primary', 'large');

// 条件类名
$isActive = true;
div('内容')->class('btn', $isActive ? 'active' : null);

// 数组格式
div('内容')->class(['btn', 'btn-primary']);
```

#### `className(string|array|null ...$args): self`

`class()` 方法的别名，因为 `class` 是 PHP 关键字。

```php
<?php

use function Pure\HTML\div;

div('内容')->className('container');
```

#### `style(string|array|null $value): self`

设置元素的内联样式，支持字符串和数组格式。

```php
<?php

use function Pure\HTML\div;

// 字符串格式
div('内容')->style('background: #fff; padding: 20px;');

// 数组格式（内置 sty 函数）
div('内容')->style([
    'background-color' => '#fff',
    'padding' => '20px',
    'border-radius' => '8px'
]);
```

### 获取方法

#### `getTagName(): string`

获取标签名称。

```php
<?php

use function Pure\HTML\div;

$element = div('内容');
echo $element->getTagName(); // 输出: div
```

#### `getAttrs(): array`

获取所有属性的关联数组。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container')->id('main');
$attrs = $element->getAttrs();
// 返回: ['class' => 'container', 'id' => 'main']
```

#### `getAttr(string $key): string`

获取指定属性的值。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');
echo $element->getAttr('class'); // 输出: container
```

#### `getChildren(): array`

获取所有子元素。

```php
<?php

use function Pure\HTML\{div, p};

$element = div(p('段落1'), p('段落2'));
$children = $element->getChildren();
```

### 自闭合标签方法

#### `getSelfClose(): bool`

检查元素是否为自闭合标签。

```php
<?php

use function Pure\HTML\{div, img};

$div = div('内容');
echo $div->getSelfClose(); // 输出: false

$img = img()->src('image.jpg');
echo $img->getSelfClose(); // 输出: true
```

#### `setSelfClose(bool $value): self`

设置元素是否为自闭合标签。

```php
<?php

use function Pure\HTML\div;

$element = div()->setSelfClose(true);
```

### 输出方法

#### `toJSON(): array`

将元素转换为 JSON 数组格式。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');
$json = $element->toJSON();
// 返回: ['tagName' => 'div', 'children' => ['内容'], 'class' => 'container']
```

#### `toPDom(): PDom`

将元素转换为 PDom 对象（用于字符串输出）。

```php
<?php

use function Pure\HTML\div;

$element = div('内容');
$pdom = $element->toPDom();
echo $pdom; // 输出: <div>内容</div>
```

#### `toNDom(): NDom`

将元素转换为 NDom 对象（基于 DOMDocument）。

```php
<?php

use function Pure\HTML\div;

$element = div('内容');
$ndom = $element->toNDom();
```

#### `toPrint(): void`

直接输出元素的 HTML 字符串。

```php
<?php

use function Pure\HTML\div;

div('内容')->class('container')->toPrint();
// 输出: <div class="container">内容</div>
```

### 动态属性方法

Tag 类通过 `__call` 魔术方法支持动态设置任何 HTML 属性：

```php
<?php

use function Pure\HTML\{div, input, img};

// 设置 ID
div('内容')->id('main');

// 设置数据属性（注意使用下划线）
div('内容')->data_id('123')->data_type('card');

// 设置 ARIA 属性
div('内容')->aria_label('主要内容');

// 设置表单属性
input()->type('text')->name('username')->placeholder('请输入用户名');

// 设置图片属性
img()->src('image.jpg')->alt('图片描述')->width('100')->height('100');
```

## HTML 类

`Pure\Core\HTML` 继承自 Tag 类，专门用于 HTML 标签。

### 静态方法

#### `__callStatic(string $tag, array $children): HTML`

通过静态调用创建 HTML 元素。

```php
<?php

use Pure\Core\HTML;

// 直接使用 HTML 类
$div = HTML::div('内容');
$p = HTML::p('段落');
```

### 保存方法

#### `toSave(string $path, string $header = '<!DOCTYPE html>'): int|false`

将 HTML 元素保存到文件。

```php
<?php

use function Pure\HTML\{html, head, title, body, div};

$page = html(
    head(title('页面标题')),
    body(div('页面内容'))
);

$result = $page->toSave('output.html');
if ($result !== false) {
    echo "文件保存成功，写入了 {$result} 字节";
}
```

### 自闭合标签

HTML 类自动识别以下自闭合标签：
- `area`, `base`, `br`, `col`, `embed`, `hr`, `img`, `input`, `link`, `meta`, `source`, `track`, `wbr`

```php
<?php

use function Pure\HTML\{img, br, hr};

// 这些标签会自动设置为自闭合
img()->src('image.jpg')->alt('图片');
br();
hr();
```

## SVG 类

`Pure\Core\SVG` 继承自 XML 类，专门用于 SVG 标签。

### 静态方法

#### `__callStatic(string $tag, array $children): SVG`

通过静态调用创建 SVG 元素。

```php
<?php

use Pure\Core\SVG;

// 直接使用 SVG 类
$circle = SVG::circle();
$rect = SVG::rect();
```

### 自闭合标签

SVG 类自动识别以下自闭合标签：
- `animate`, `animateMotion`, `circle`, `ellipse`, `feBlend`, `feColorMatrix`, `feDisplacementMap`, `feDropShadow`, `feGaussianBlur`, `feImage`, `image`, `line`, `mpath`, `path`, `polygon`, `polyline`, `rect`, `stop`, `use`

```php
<?php

use function Pure\SVG\{svg, circle, rect};

$graphic = svg(
    circle()->cx('50')->cy('50')->r('40')->fill('red'),
    rect()->x('10')->y('10')->width('80')->height('80')->fill('blue')
)->width('100')->height('100');
```

## XML 类

`Pure\Core\XML` 继承自 Tag 类，用于创建 XML 元素。

### 静态方法

#### `__callStatic(string $tag, array $children): XML`

通过静态调用创建 XML 元素。

```php
<?php

use Pure\Core\XML;

// 创建 XML 元素
$customer = XML::customer(
    XML::name('客户名称'),
    XML::address(
        XML::street('街道地址'),
        XML::city('城市'),
        XML::zip('邮编')
    )
)->id('123');
```

### 保存方法

#### `toSave(string $path, string $header = '<?xml version="1.0"?>'): int|false`

将 XML 元素保存到文件。

```php
<?php

use Pure\Core\XML;

$xml = XML::root(
    XML::item('内容1'),
    XML::item('内容2')
);

$result = $xml->toSave('output.xml');
if ($result !== false) {
    echo "XML 文件保存成功";
}
```

## Raw 类

`Pure\Core\Raw` 用于表示原始的 HTML 或 XML 内容，不会被转义。

### 构造方法

#### `__construct(RawType $type, string $content)`

创建 Raw 对象。

```php
<?php

use Pure\Core\Raw;
use Pure\Core\RawType;

// 创建原始 HTML 内容
$rawHtml = new Raw(RawType::HTML, '<strong>粗体文本</strong>');

// 创建原始 XML 内容
$rawXml = new Raw(RawType::XML, '<item>内容</item>');
```

### 输出方法

#### `__toString(): string`

将 Raw 对象转换为字符串。

```php
<?php

use function Pure\Utils\rawHtml;

$raw = rawHtml('<em>斜体文本</em>');
echo $raw; // 输出: <em>斜体文本</em>
```

#### `toJSON(): array`

将 Raw 对象转换为 JSON 数组格式。

```php
<?php

use function Pure\Utils\rawHtml;

$raw = rawHtml('<span>内容</span>');
$json = $raw->toJSON();
// 返回: ['type' => 'HTML', 'content' => '<span>内容</span>']
```

### 工具函数

推荐使用工具函数创建 Raw 对象：

```php
<?php

use function Pure\Utils\{rawHtml, rawXml};
use function Pure\HTML\div;

// 使用 rawHtml 函数
div(
    rawHtml('<strong>这是粗体</strong>'),
    rawHtml('<em>这是斜体</em>')
)->toPrint();

// 使用 rawXml 函数
$xmlContent = rawXml('<item id="1">内容</item>');
```

## PDom 类

`Pure\Core\PDom` 是用于字符串输出的 DOM 表示类，继承自 Dom 抽象类。

### 输出方法

#### `__toString(): string`

将 PDom 对象转换为 HTML/XML 字符串。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');
$pdom = $element->toPDom();
echo $pdom; // 输出: <div class="container">内容</div>
```

## NDom 类

`Pure\Core\NDom` 是基于 DOMDocument 的 DOM 表示类，继承自 Dom 抽象类。

### 输出方法

#### `__toString(): string`

将 NDom 对象转换为 HTML/XML 字符串。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');
$ndom = $element->toNDom();
echo $ndom; // 输出: <div class="container">内容</div>
```

#### `toDom(): DOMElement`

获取底层的 DOMElement 对象。

```php
<?php

use function Pure\HTML\div;

$element = div('内容');
$ndom = $element->toNDom();
$domElement = $ndom->toDom(); // 返回 DOMElement 对象
```
