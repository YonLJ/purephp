# HTML 类

`Pure\Core\HTML` 继承自 Tag 类，专门用于 HTML 标签。

## 创建 HTML 元素

PurePHP 提供两种创建 HTML 元素的方式：

### 1. 魔术静态方法（推荐用于预定义标签）

```php
<?php

use Pure\Core\HTML;

// 直接使用 HTML 类的魔术方法
$div = HTML::div('内容');
$p = HTML::p('段落');
$span = HTML::span('文本')->class('highlight');
```

**优点：**
- 语法简洁优雅
- 适用于任何标签名
- 非常适合自定义或非标准标签

**使用场景：**
- 自定义 HTML 标签
- Web 组件
- 非标准 HTML 元素

### 2. 构造函数方法（推荐用于性能关键代码）

```php
<?php

use Pure\Core\HTML;

// 直接使用构造函数
$div = new HTML('div', ['内容']);
$p = new HTML('p', ['段落']);
$span = (new HTML('span', ['文本']))->class('highlight');

// 对于没有子元素的标签，可以省略第二个参数
$img = new HTML('img');
$br = new HTML('br');
```

**优点：**
- 更好的性能（无魔术方法开销）
- 更好的 IDE 支持和类型检查
- 更明确

**使用场景：**
- 性能关键应用
- 需要最大类型安全时
- 库开发

## 保存方法

### `toSave(string $path, string $header = '<!DOCTYPE html>'): int|false`

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

## 自闭合标签

HTML 类自动识别以下自闭合标签：
- `area`, `base`, `br`, `col`, `embed`, `hr`, `img`, `input`, `link`, `meta`, `source`, `track`, `wbr`

```php
<?php

use function Pure\HTML\{img, br, hr};

// 这些标签自动设置为自闭合
img()->src('image.jpg')->alt('图片');
br();
hr();
```

## 示例

### 基本 HTML 结构

```php
<?php

use function Pure\HTML\{html, head, meta, title, body, div, h1, p};

$page = html(
    head(
        meta()->charset('UTF-8'),
        meta()->name('viewport')->content('width=device-width, initial-scale=1.0'),
        title('我的页面')
    ),
    body(
        div(
            h1('欢迎'),
            p('这是我的网站。')
        )->class('container')
    )
)->lang('zh-CN');

echo $page;
```

### 表单创建

```php
<?php

use function Pure\HTML\{form, div, label, input, textarea, button};

$contactForm = form(
    div(
        label('姓名:')->for('name'),
        input()->type('text')->id('name')->name('name')->required()
    )->class('form-group'),
    div(
        label('邮箱:')->for('email'),
        input()->type('email')->id('email')->name('email')->required()
    )->class('form-group'),
    div(
        label('消息:')->for('message'),
        textarea('')->id('message')->name('message')->rows('5')->required()
    )->class('form-group'),
    button('发送消息')->type('submit')
)->method('POST')->action('/contact');

echo $contactForm;
```

### 使用魔术方法的自定义组件

```php
<?php

use Pure\Core\HTML;

// 创建自定义 Web 组件
$customCard = HTML::cardComponent(
    HTML::cardHeader('卡片标题'),
    HTML::cardBody('卡片内容在这里'),
    HTML::cardFooter('卡片页脚')
)->data_component('card')->class('custom-card');

echo $customCard;
```

### 性能关键的生成

```php
<?php

use Pure\Core\HTML;

// 高效生成大型列表
$items = [];
for ($i = 1; $i <= 1000; $i++) {
    $items[] = new HTML('li', ["项目 $i"]);
}

$list = new HTML('ul', $items);
echo $list;
```
