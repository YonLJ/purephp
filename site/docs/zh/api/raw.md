# Raw 类

`Pure\Core\Raw` 表示不会被转义的原始 HTML 或 XML 内容。

## 为什么原始内容很重要

字符串内容一律会被转义，因此形似标记的文本会显示出来，而不会被解析：

```php
<?php

use function Pure\HTML\div;

// 字符串内容被转义
div('<p>你好 <strong>世界</strong></p>')->toPrint();
// 输出: <div>&lt;p&gt;你好 &lt;strong&gt;世界&lt;/strong&gt;&lt;/p&gt;</div>
```

Raw 类用于在需要可信 HTML/XML 标记时按原样输出：

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// 原始内容保留 HTML 标签
div(rawHtml('<p>你好 <strong>世界</strong></p>'))->toPrint();
// 输出: <div><p>你好 <strong>世界</strong></p></div>
```

## 构造函数

### `__construct(RawType $type, string $content)`

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

## 输出方法

### `__toString(): string`

将 Raw 对象转换为字符串。

```php
<?php

use function Pure\Utils\rawHtml;

$raw = rawHtml('<em>斜体文本</em>');
echo $raw; // 输出: <em>斜体文本</em>
```

### `toJSON(): array`

将 Raw 对象转换为 JSON 数组格式。

```php
<?php

use function Pure\Utils\rawHtml;

$raw = rawHtml('<span>内容</span>');
$json = $raw->toJSON();
// 返回: ['type' => 'HTML', 'content' => '<span>内容</span>']
```

## 工具函数

建议使用工具函数来创建 Raw 对象：

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

## 示例

### 嵌入原始 HTML

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// 嵌入预格式化的 HTML 内容
$content = div(
    rawHtml('<h2>原始 HTML 内容</h2>'),
    rawHtml('<p>这个内容<strong>不会</strong>被转义。</p>'),
    rawHtml('<script>console.log("JavaScript 可以工作!");</script>')
)->class('raw-content');

echo $content;
```

### 包含外部内容

```php
<?php

use function Pure\HTML\{div, h1};
use function Pure\Utils\rawHtml;

// 包含来自外部源的内容
$externalHtml = file_get_contents('external-content.html');

$page = div(
    h1('我的页面'),
    rawHtml($externalHtml)
)->class('page');

echo $page;
```

### 模板包含

```php
<?php

use function Pure\HTML\{html, head, title, body};
use function Pure\Utils\rawHtml;

function includeTemplate(string $templatePath): string
{
    ob_start();
    include $templatePath;
    return ob_get_clean();
}

$page = html(
    head(title('我的网站')),
    body(
        rawHtml(includeTemplate('header.php')),
        rawHtml(includeTemplate('content.php')),
        rawHtml(includeTemplate('footer.php'))
    )
);

echo $page;
```

### 带有原始内容的 XML

```php
<?php

use Pure\Core\XML;
use function Pure\Utils\rawXml;

$document = XML::document(
    XML::metadata(
        XML::title('包含原始内容的文档')
    ),
    XML::content(
        rawXml('<![CDATA[这是包含 <特殊> 字符的原始 XML 内容]]>')
    )
);

echo $document;
```

### 条件原始内容

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

$isDevelopment = true;

$page = div(
    '这里是主要内容',
    $isDevelopment ? rawHtml('<div class="debug">调试信息</div>') : ''
)->class('page');

echo $page;
```

## 安全考虑

⚠️ **重要**：原始内容不会被转义，所以在使用用户提供的内容时要小心：

```php
<?php

use function Pure\HTML\div;
use function Pure\Utils\rawHtml;

// ❌ 危险 - 永远不要对用户输入这样做
$userInput = $_POST['content']; // 可能包含恶意脚本
$dangerous = div(rawHtml($userInput));

// ✅ 安全 - 字符串子节点会自动转义
$userInput = $_POST['content'];
$safe = div($userInput);

// ✅ 安全 - 仅对可信内容使用 Raw
$trustedHtml = '<strong>管理员消息</strong>';
$safe = div(rawHtml($trustedHtml));
```
