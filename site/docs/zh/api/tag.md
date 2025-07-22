# Tag 类

`Pure\Core\Tag` 是所有 HTML 和 SVG 标签的基础抽象类。

## 属性方法

### `class(string|array|null ...$args): self`

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

### `className(string|array|null ...$args): self`

`class()` 方法的别名，因为 `class` 是 PHP 关键字。

```php
<?php

use function Pure\HTML\div;

div('内容')->className('container');
```

### `style(string|array|null $value): self`

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

## 获取方法

### `getTagName(): string`

获取标签名。

```php
<?php

use function Pure\HTML\div;

$element = div('内容');
echo $element->getTagName(); // 输出: div
```

### `getAttrs(): array`

获取所有属性的关联数组。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container')->id('main');
$attrs = $element->getAttrs();
// 返回: ['class' => 'container', 'id' => 'main']
```

### `getAttr(string $key): string`

获取特定属性的值。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');
echo $element->getAttr('class'); // 输出: container
```

### `getChildren(): array`

获取所有子元素。

```php
<?php

use function Pure\HTML\{div, p};

$element = div(p('段落 1'), p('段落 2'));
$children = $element->getChildren();
```

## 自闭合标签方法

### `getSelfClose(): bool`

检查元素是否为自闭合标签。

```php
<?php

use function Pure\HTML\{div, img};

$div = div('内容');
echo $div->getSelfClose(); // 输出: false

$img = img()->src('image.jpg');
echo $img->getSelfClose(); // 输出: true
```

### `setSelfClose(bool $value): self`

设置元素是否为自闭合标签。

```php
<?php

use function Pure\HTML\div;

$element = div()->setSelfClose(true);
```

## 输出方法

### `toJSON(): array`

将元素转换为 JSON 数组格式。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');
$json = $element->toJSON();
// 返回: ['tagName' => 'div', 'children' => ['内容'], 'class' => 'container']
```

### `toPDom(): PDom`

将元素转换为 PDom 对象（用于字符串输出）。

```php
<?php

use function Pure\HTML\div;

$element = div('内容');
$pdom = $element->toPDom();
echo $pdom; // 输出: <div>内容</div>
```

### `toNDom(): NDom`

将元素转换为 NDom 对象（基于 DOMDocument）。

```php
<?php

use function Pure\HTML\div;

$element = div('内容');
$ndom = $element->toNDom();
```

### `toPrint(): void`

直接输出元素的 HTML 字符串。

```php
<?php

use function Pure\HTML\div;

div('内容')->class('container')->toPrint();
// 输出: <div class="container">内容</div>
```

## 动态属性方法

Tag 类通过 `__call` 魔术方法支持动态设置任何 HTML 属性：

```php
<?php

use function Pure\HTML\{div, input, img};

// 设置 ID
div('内容')->id('main');

// 设置 data 属性（注意使用下划线）
div('内容')->data_id('123')->data_type('card');

// 设置 ARIA 属性
div('内容')->aria_label('主要内容');

// 设置表单属性
input()->type('text')->name('username')->placeholder('输入用户名');

// 设置图片属性
img()->src('image.jpg')->alt('图片描述')->width('100')->height('100');
```
