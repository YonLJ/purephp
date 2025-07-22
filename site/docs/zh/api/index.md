# API 参考

本节为所有 PurePHP 类及其方法提供全面的文档。

## 核心类

PurePHP 由几个核心类组成，它们协同工作提供强大的模板系统：

### [Tag 类](/zh/api/tag)
所有 HTML 和 SVG 标签的基础抽象类。为属性、子元素和输出方法提供通用功能。

### [HTML 类](/zh/api/html)
专门为 HTML 元素扩展 Tag 类。包括 HTML 特定功能，如自闭合标签检测和文件保存。

### [SVG 类](/zh/api/svg)
为创建 SVG 图形扩展 XML 类。自动处理 SVG 特定的自闭合标签和命名空间。

### [XML 类](/zh/api/xml)
为创建 XML 文档扩展 Tag 类。非常适合配置文件、数据导出和 API 响应。

### [Raw 类](/zh/api/raw)
表示绕过转义的原始 HTML 或 XML 内容。用于包含预格式化内容或模板。

### [DOM 类](/zh/api/dom)
PDom 和 NDom 类提供不同的 DOM 表示方法 - PDom 用于性能，NDom 用于高级操作。

## 快速参考

### 创建元素

```php
<?php

use Pure\Core\{HTML, SVG, XML};
use function Pure\HTML\div;
use function Pure\SVG\circle;

// 函数方式（推荐用于标准标签）
$element1 = div('内容');

// 魔术静态方法（推荐用于自定义标签）
$element2 = HTML::customTag('内容');

// 构造函数（推荐用于性能）
$element3 = new HTML('div', ['内容']);
```

### 通用方法

所有基于 Tag 的类都共享这些通用方法：

- `class()` / `className()` - 设置 CSS 类
- `style()` - 设置内联样式
- `id()`, `data_*()`, `aria_*()` - 设置属性
- `getTagName()`, `getAttrs()`, `getChildren()` - 获取信息
- `toJSON()`, `toPrint()`, `__toString()` - 输出方法

### 性能指南

- **使用函数** 用于标准 HTML/SVG 标签
- **使用魔术方法** 用于自定义或动态标签
- **使用构造函数** 用于性能关键代码
- **使用 Raw 类** 用于预格式化内容

## 类层次结构

```
Tag (抽象)
├── HTML
├── XML
│   └── SVG
└── Raw

Dom (抽象)
├── PDom
└── NDom
```

## 下一步

- 浏览各个类文档以获取详细示例
- 查看[指南](/zh/guide/)了解实用使用模式
- 参见 [SVG 和 XML 支持](/zh/guide/svg-xml) 了解图形和数据处理
