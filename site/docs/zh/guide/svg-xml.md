# SVG 和 XML 支持

PurePHP 为创建 SVG 图形和 XML 文档提供全面支持，使用与 HTML 相同的优雅语法。

*HTML、SVG 和 XML 标签实例都继承自 `Tag`，因此它们中的任何一个都可以包装进 `Compile::shape()` 并用数据渲染——参见[编译组件](/zh/guide/compiled)。下面的 SVG 部分属于标签 API 参考，使用 `render()` / `toPrint()` 即时渲染；XML 部分使用编译路径。*

## SVG 支持

### 基本 SVG 创建

使用魔术静态方法或构造函数创建 SVG 图形：

```php
<?php

use function Pure\SVG\{svg, circle, rect, path};
use Pure\Core\SVG;

// 使用函数方式（推荐用于预定义标签）
$graphic = svg(
    circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->fill('red'),
    rect()
        ->x('10')
        ->y('10')
        ->width('80')
        ->height('80')
        ->fill('blue')
)->width('100')->height('100');

echo $graphic; // 输出 SVG 标记
```

### 魔术静态方法 vs 构造函数

#### 魔术静态方法（适合自定义标签）

```php
<?php

use Pure\Core\SVG;

// 任何 SVG 标签的简洁语法
$customElement = SVG::customTag(
    SVG::innerElement('content')
)->customAttribute('value');

// 非常适合非标准或自定义 SVG 元素
$webComponent = SVG::myCustomSvgElement()
    ->data_id('unique')
    ->class('custom-svg');
```

#### 构造函数方法（性能优化）

```php
<?php

use Pure\Core\SVG;

// 直接使用构造函数获得更好性能
$customElement = new SVG('customTag', [
    new SVG('innerElement', ['content'])
])->customAttribute('value');

// 更适合性能关键的应用
$webComponent = (new SVG('myCustomSvgElement'))
    ->data_id('unique')
    ->class('custom-svg');
```

### 复杂 SVG 示例

#### 创建图标

```php
<?php

use function Pure\SVG\{svg, path, g};

function ChevronIcon($direction = 'right'): SVG
{
    $rotation = match($direction) {
        'up' => 'rotate(-90 12 12)',
        'down' => 'rotate(90 12 12)',
        'left' => 'rotate(180 12 12)',
        default => ''
    };

    return svg(
        path('M9 18l6-6-6-6')
            ->stroke('currentColor')
            ->stroke_width('2')
            ->fill('none')
            ->stroke_linecap('round')
            ->stroke_linejoin('round')
            ->transform($rotation)
    )->width('24')->height('24')->viewBox('0 0 24 24');
}

// 使用
echo ChevronIcon('down')->class('icon');
```

#### 动画 SVG

```php
<?php

use Pure\Core\SVG;

$animatedCircle = SVG::svg(
    SVG::circle()
        ->cx('50')
        ->cy('50')
        ->r('40')
        ->fill('red'),
    SVG::animate()
        ->attributeName('r')
        ->values('40;45;40')
        ->dur('2s')
        ->repeatCount('indefinite')
)->width('100')->height('100');
```

## XML 支持

XML 标签同样继承自 `Tag`，因此文档以编译形状构建：树及其槽位每个进程只创建一次，每次导出时绑定数据并保存或打印。

### 编译 XML 文档

`AddressShape()` 渲染一条记录；`city` 是可选的，仅在数据提供时才出现：

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;
use Pure\Core\XML;

function AddressShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        XML::address(
            XML::street(Slot::text('street')),
            Slot::if('city', Compile::shape(XML::city(Slot::text('city')))),
            XML::state(Slot::text('state')),
            XML::zip(Slot::text('zip'))
        )
    );
}

$page = Compile::shape(
    XML::customers(
        XML::customer(
            XML::name('Charter Group'),
            Slot::each('addresses', AddressShape())
        )->id('55000')
    )
);

$data = [
    'addresses' => [
        ['street' => '100 Main', 'city' => 'Framingham', 'state' => 'MA', 'zip' => '01701'],
        ['street' => '720 Prospect', 'city' => 'Framingham', 'state' => 'MA', 'zip' => '01701'],
        ['street' => '120 Ridge', 'state' => 'MA', 'zip' => '01760'],
    ],
];

$page->compile()->save('./example.xml', $data, '<?xml version="1.0"?>');
```

`Slot::each()` 为每条记录渲染一个 `AddressShape()`，`Slot::if()` 对没有 `city` 的记录跳过该元素——缺失的键为 false，且绝不抛出异常。同一个形状可以用 `$page($data)` 或 `$page->print($data)` 渲染为字符串；`Renderer::save()` 只是为文档加上头部。

### 数据驱动的元素

标签名在构建时固定，因此动态的键和值成为槽位——这里是设置列表上的 `key` 属性和文本内容：

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;
use Pure\Core\XML;

$setting = Compile::shape(
    XML::setting(Slot::text('value'))->key(Slot::attr('key'))
);

$config = Compile::shape(
    XML::configuration(Slot::each('settings', $setting))
);

$config->print(['settings' => [
    ['key' => 'host', 'value' => 'localhost'],
    ['key' => 'port', 'value' => '3306'],
    ['key' => 'debug', 'value' => 'true'],
]]);
```

当结构本身必须随数据变化时，请使用 `Slot::if()` 或 `Slot::eachAny()`；形状的标签集合无法变化。

## 性能考虑

### 何时使用魔术方法 vs 构造函数

**使用魔术静态方法当：**
- 创建自定义或非标准标签
- 原型设计和开发
- 代码可读性是优先考虑
- 使用动态标签名

**使用构造函数当：**
- 性能至关重要
- 构建库或框架
- 需要最大类型安全
- 处理大型文档

### 性能比较

```php
<?php

use Pure\Core\HTML;

// 魔术方法（稍慢但更优雅）
$element1 = HTML::customTag('content')->customAttr('value');

// 构造函数（更快，更明确）
$element2 = (new HTML('customTag', ['content']))->customAttr('value');

// 对于预定义标签，使用函数（两全其美）
use function Pure\HTML\div;
$element3 = div('content')->customAttr('value');
```

## 重要：字符串内容会被转义

⚠️ **安全提示**：字符串内容一律会被转义，因此形似 XML/SVG 的文本是安全的，并且会原样显示：

```php
<?php

use Pure\Core\XML;
use function Pure\Utils\rawXml;

// ✅ 字符串中的 XML 标签会被转义，不会被解析
XML::root('<item>This stays visible</item>')->toPrint();
// 输出: <root>&lt;item&gt;This stays visible&lt;/item&gt;</root>

// ✅ 使用 rawXml 输出 XML 内容
XML::root(rawXml('<item>This is preserved</item>'))->toPrint();
// 输出: <root><item>This is preserved</item></root>
```

**何时使用 rawXml/rawHtml：**
- 包含 CDATA 部分
- 嵌入外部 XML/SVG 内容
- 处理预格式化的标记
- 包含复杂的嵌套结构

两条渲染路径行为一致；绑定的数据由 `Slot::text()` / `Slot::attr()` 转义，当数据必须保留标记时，`Slot::raw()` 是 `rawXml()` 的等价原样输出。

## 最佳实践

1. **对预定义的 HTML/SVG 标签使用函数**——它们提供性能和可读性的最佳平衡
2. **对自定义标签使用魔术方法**——当你需要动态创建标签时
3. **对性能关键代码使用构造函数**——当构建库或处理大型文档时
4. **对可信内容使用 rawXml/rawHtml**——当你需要保留标记结构时
5. **根据需要组合方法**——你可以根据具体用例混合搭配

## 下一步

- [API 参考](/zh/api/)——完整的 API 文档
- [组件](/zh/guide/components)——学习创建可重用组件
- [工具函数](/zh/guide/utils)——探索辅助函数
