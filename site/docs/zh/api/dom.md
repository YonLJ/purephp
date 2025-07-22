# DOM 类

PurePHP 为不同用例提供两个 DOM 表示类。

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

### 使用场景

PDom 针对字符串输出进行了优化，是大多数场景的默认选择：

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

// 转换为 PDom 进行字符串输出
$pdom = $page->toPDom();
echo $pdom; // 输出完整的 HTML
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

### 使用场景

当您需要与 PHP 的 DOMDocument 交互时，NDom 很有用：

```php
<?php

use function Pure\HTML\{div, p};

$element = div(
    p('第一段'),
    p('第二段')
);

$ndom = $element->toNDom();
$domElement = $ndom->toDom();

// 使用 DOMDocument 方法
$document = $domElement->ownerDocument;
$xpath = new DOMXPath($document);
$paragraphs = $xpath->query('//p');

foreach ($paragraphs as $p) {
    echo $p->textContent . "\n";
}
```

## 选择 PDom 还是 NDom

### 使用 PDom 当：
- 您需要简单的字符串输出
- 性能很重要
- 您正在为 Web 响应生成 HTML/XML
- 您不需要 DOM 操作

### 使用 NDom 当：
- 您需要在创建后操作 DOM
- 您想要使用 XPath 查询
- 您需要与现有的 DOMDocument 代码集成
- 您需要高级 DOM 功能

## 示例

### 性能比较

```php
<?php

use function Pure\HTML\div;

$element = div('内容')->class('container');

// PDom - 简单输出更快
$pdom = $element->toPDom();
$html1 = (string)$pdom;

// NDom - 更多功能但较慢
$ndom = $element->toNDom();
$html2 = (string)$ndom;

// 两者产生相同的输出
assert($html1 === $html2);
```

### 使用 NDom 进行 DOM 操作

```php
<?php

use function Pure\HTML\{div, p};

$container = div(
    p('原始内容')
)->class('container');

$ndom = $container->toNDom();
$domElement = $ndom->toDom();
$document = $domElement->ownerDocument;

// 使用 DOMDocument 添加新段落
$newP = $document->createElement('p', '通过 DOM 添加');
$domElement->appendChild($newP);

echo $ndom; // 输出包含两个段落的容器
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

$ndom = $content->toNDom();
$domElement = $ndom->toDom();
$document = $domElement->ownerDocument;

// 使用 XPath 查找特定元素
$xpath = new DOMXPath($document);

// 查找所有段落
$paragraphs = $xpath->query('//p');
echo "找到 {$paragraphs->length} 个段落\n";

// 查找段落内的 span
$spans = $xpath->query('//p/span');
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

// 转换为 NDom 进行 DOM 操作
$ndom = $table->toNDom();
$domTable = $ndom->toDom();
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

echo $ndom; // 输出修改后的表格
```
