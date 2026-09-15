# DOM 类

PurePHP 直接从标签树渲染字符串；对于高级 DOM 操作，提供基于 DOMDocument 的 Dom 表示类。

## 字符串输出

所有标签都直接从标签树渲染为 HTML 字符串，无需生成中间 DOM 副本：

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');
echo $element; // 输出: <div class="container">内容</div>
$html = $element->render();
```

### 使用场景

字符串输出是大多数场景的默认选择：
- 您需要简单的字符串输出
- 性能很重要
- 您正在为 Web 响应生成 HTML/XML
- 您不需要 DOM 操作

```php
<?php

use function Pure\HTML\{html, head, title, body, div, h1, p};

$page = html(
    head(title('我的页面')),
    body(
        div(
            h1('欢迎'),
            p('这是我的网站。')
        )->class('container')
    )
);

echo $page; // 输出完整的 HTML
```

## Dom 类

`Pure\Core\Dom` 是基于 DOMDocument 的 DOM 表示类。

### 输出方法

#### `__toString(): string`

将 Dom 对象转换为 HTML/XML 字符串。

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');
$dom = $element->toDom();
echo $dom; // 输出: <div class="container">内容</div>
```

#### `toDom(): DOMElement`

获取底层的 DOMElement 对象。

```php
<?php

use function Pure\HTML\div;

$element = div('内容');
$dom = $element->toDom();
$domElement = $dom->toDom(); // 返回 DOMElement 对象
```

### 使用场景

当您需要与 PHP 的 DOMDocument 交互时，Dom 很有用：

```php
<?php

use function Pure\HTML\{div, p};

$element = div(
    p('第一段'),
    p('第二段')
);

$dom = $element->toDom();
$domElement = $dom->toDom();

// 使用 DOMDocument 方法
$document = $domElement->ownerDocument;
$xpath = new DOMXPath($document);

// 节点未挂载到文档根节点，因此需要相对该元素查询
$paragraphs = $xpath->query('.//p', $domElement);

foreach ($paragraphs as $p) {
    echo $p->textContent . "\n";
}
```

### 使用 Dom 当：
- 您需要在创建后操作 DOM
- 您想要使用 XPath 查询
- 您需要与现有的 DOMDocument 代码集成
- 您需要高级 DOM 功能

### 与字符串输出的差异

对于普通标签，两种输出路径产生的标记相同，但并非逐字节一致：

- 两条路径都会转义文本子节点和属性值。`render()` 仅对文本子节点避免二次编码（`&copy;` 保持为 `&copy;`），而属性值和 DOM 序列化的文本都会被再次编码（`&copy;` 变成 `&amp;copy;`）。
- HTML 序列化默认使用双引号，仅当值中包含 `"` 时改用单引号；XML/SVG 输出（`saveXML`）始终使用双引号并将 `"` 转义为 `&quot;`；`render()` 始终使用双引号。
- 空元素与自闭合元素按 DOM 风格序列化（`<br>`、`<child/>`），而 `render()` 输出 `<br />`。
- Raw HTML 子节点会被 HTML 解析器解析后导入 DOM，因此畸形标记会被修正。
- 格式不合法的 Raw XML 会抛出异常，而不是被静默丢弃。

### 从 1.x 迁移

`PDom`、`NDom`、`toPDom()` 和 `toNDom()` 已被移除：

- 使用 `(string)$element` 或 `$element->render()` 替代 `(string)$element->toPDom()`。
- `$element->toDom()` 返回 `Dom` 对象，替代 `$element->toNDom()`。

## 示例

### 使用 Dom 进行 DOM 操作

```php
<?php

use function Pure\HTML\{div, p};

$container = div(
    p('原始内容')
)->class('container');

$dom = $container->toDom();
$domElement = $dom->toDom();
$document = $domElement->ownerDocument;

// 使用 DOMDocument 添加新段落
$newP = $document->createElement('p', '通过 DOM 添加');
$domElement->appendChild($newP);

echo $dom; // 输出包含两个段落的容器
```

### XPath 查询

```php
<?php

use function Pure\HTML\{div, p, span};

$content = div(
    p('第一段'),
    p(span('高亮文本'), ' 在第二段中'),
    p('第三段')
)->class('content');

$dom = $content->toDom();
$domElement = $dom->toDom();
$document = $domElement->ownerDocument;

// 使用 XPath 查找特定元素
$xpath = new DOMXPath($document);

// 查找所有段落
$paragraphs = $xpath->query('.//p', $domElement);
echo "找到 {$paragraphs->length} 个段落\n";

// 查找段落内的 span
$spans = $xpath->query('.//p/span', $domElement);
foreach ($spans as $span) {
    echo "Span 内容: {$span->textContent}\n";
}
```

### 与现有 DOM 代码集成

```php
<?php

use function Pure\HTML\{table, tr, td};

// 使用 PurePHP 创建表格
$table = table(
    tr(td('单元格 1'), td('单元格 2')),
    tr(td('单元格 3'), td('单元格 4'))
)->class('data-table');

// 转换为 Dom 进行 DOM 操作
$dom = $table->toDom();
$domTable = $dom->toDom();
$document = $domTable->ownerDocument;

// 使用 DOM 方法添加属性
$domTable->setAttribute('border', '1');
$domTable->setAttribute('cellpadding', '5');

// 添加新行
$newRow = $document->createElement('tr');
$cell1 = $document->createElement('td', '单元格 5');
$cell2 = $document->createElement('td', '单元格 6');
$newRow->appendChild($cell1);
$newRow->appendChild($cell2);
$domTable->appendChild($newRow);

echo $dom; // 输出修改后的表格
```
