# SVG 和 XML 支持

PurePHP 为创建 SVG 图形和 XML 文档提供全面支持，使用与 HTML 相同的优雅语法。

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
    SVG::innerElement('内容')
)->customAttribute('值');

// 非常适合非标准或自定义 SVG 元素
$webComponent = SVG::myCustomSvgElement()
    ->data_id('unique')
    ->class('custom-svg');
```

#### 构造函数方法（性能优化）

```php
<?php

use Pure\Core\SVG;

// 直接构造函数获得更好性能
$customElement = new SVG('customTag', [
    new SVG('innerElement', ['内容'])
])->customAttribute('值');

// 更适合性能关键应用
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

### 基本 XML 创建

创建结构化数据的 XML 文档：

```php
<?php

use Pure\Core\XML;

// 使用魔术静态方法
$document = XML::root(
    XML::metadata(
        XML::title('文档标题'),
        XML::author('作者姓名'),
        XML::created('2024-01-01')
    ),
    XML::content(
        XML::section(
            XML::heading('第一节'),
            XML::paragraph('这是第一节的内容。')
        )->id('section-1')
    )
);

echo $document;
```

### XML 的构造函数方式

```php
<?php

use Pure\Core\XML;

// 使用构造函数获得更好性能
$document = new XML('root', [
    new XML('metadata', [
        new XML('title', ['文档标题']),
        new XML('author', ['作者姓名']),
        new XML('created', ['2024-01-01'])
    ]),
    new XML('content', [
        (new XML('section', [
            new XML('heading', ['第一节']),
            new XML('paragraph', ['这是第一节的内容。'])
        ]))->id('section-1')
    ])
]);
```

### XML 配置文件

```php
<?php

use Pure\Core\XML;

function createConfig(array $settings): XML
{
    $config = XML::configuration();

    foreach ($settings as $key => $value) {
        if (is_array($value)) {
            $section = XML::section()->name($key);
            foreach ($value as $subKey => $subValue) {
                $section = $section->appendChild(
                    XML::setting($subValue)->key($subKey)
                );
            }
            $config = $config->appendChild($section);
        } else {
            $config = $config->appendChild(
                XML::setting($value)->key($key)
            );
        }
    }

    return $config;
}

// 使用
$settings = [
    'database' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'myapp'
    ],
    'debug' => 'true'
];

$configXml = createConfig($settings);
$configXml->toSave('config.xml');
```

### 数据导出到 XML

```php
<?php

use Pure\Core\XML;

function exportUsersToXml(array $users): XML
{
    $usersXml = XML::users();

    foreach ($users as $userData) {
        $user = XML::user(
            XML::name($userData['name']),
            XML::email($userData['email']),
            XML::role($userData['role'])
        )->id($userData['id']);

        if (!empty($userData['addresses'])) {
            $addresses = XML::addresses();
            foreach ($userData['addresses'] as $addr) {
                $addresses = $addresses->appendChild(
                    XML::address(
                        XML::street($addr['street']),
                        XML::city($addr['city']),
                        XML::zip($addr['zip'])
                    )->type($addr['type'])
                );
            }
            $user = $user->appendChild($addresses);
        }

        $usersXml = $usersXml->appendChild($user);
    }

    return $usersXml;
}
```

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
$element1 = HTML::customTag('内容')->customAttr('值');

// 构造函数（更快，更明确）
$element2 = (new HTML('customTag', ['内容']))->customAttr('值');

// 对于预定义标签，使用函数（两全其美）
use function Pure\HTML\div;
$element3 = div('内容')->customAttr('值');
```

## 重要：字符串内容过滤

⚠️ **安全提示**：包含 XML/SVG 标签的字符串内容会被自动过滤：

```php
<?php

use Pure\Core\XML;
use function Pure\Utils\rawXml;

// ❌ 字符串中的 XML 标签会被过滤
XML::root('<item>这会被过滤</item>')->toPrint();
// 输出: <root>这会被过滤</root>

// ✅ 使用 rawXml 保留 XML 内容
XML::root(rawXml('<item>这会被保留</item>'))->toPrint();
// 输出: <root><item>这会被保留</item></root>
```

**何时使用 rawXml/rawHtml：**
- 包含 CDATA 部分
- 嵌入外部 XML/SVG 内容
- 处理预格式化的标记
- 包含复杂的嵌套结构

## 最佳实践

1. **对预定义的 HTML/SVG 标签使用函数** - 它们提供性能和可读性的最佳平衡
2. **对自定义标签使用魔术方法** - 当您需要动态标签创建时
3. **对性能关键代码使用构造函数** - 当构建库或处理大型文档时
4. **对可信内容使用 rawXml/rawHtml** - 当您需要保留标记结构时
5. **根据需要组合方法** - 您可以根据具体用例混合搭配

## 下一步

- [API 参考](/zh/api/) - 完整的 API 文档
- [组件](/zh/guide/components) - 学习创建可重用组件
- [工具函数](/zh/guide/utils) - 探索辅助函数
