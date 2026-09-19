# 基本概念

本指南介绍 PurePHP 的核心概念。

## 选择路径

一条规则覆盖两条路径：

- **输出由数据驱动 → 槽位与形状**：把值替换为 `Slot` 占位符，用 `Compile::shape()` 包装这棵树，然后按请求绑定普通数据。
- **代码片段、原型或调试输出 → 立即渲染**：用真实值构建树并调用 `print()` 或 `render()`。

| 动作 | `Tag`（即时） | `Shape`（编译） |
| --- | --- | --- |
| 字符串 | `render()` | `$shape($data)` |
| 输出 | `print()` | `print($data)` |
| 文件 | `save($path)` | `save($path, $data)` |
| 调试 | `toJSON()` | `compile()->source` |

每个名字只出现一次：`render()` 返回字符串，`print()` 输出，`save()` 写文件（并补上根标签的文档声明），`toJSON()` / `source` 暴露结构用于调试。

## 标签树

PurePHP 用 PHP 对象表示 HTML。标签辅助函数构建树，方法链式调用设置属性：

```php
<?php

use function Pure\HTML\{div, h1, p};

$element = div(
    h1('Title'),
    p('Content')
)->class('container');

echo $element; // <div class="container"><h1>Title</h1><p>Content</p></div>
```

渲染时文本子节点与属性值会被转义；`Raw` 子节点原样输出。包含数据的标签树通过 `render()`、`print()` 或 `__toString()` 立即渲染。这条路径适合代码片段与调试。

## 形状与槽位

**形状**是同样的树，但*不含数据*：动态值被替换为 `Slot` 占位符。形状描述结构，数据稍后到达。

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, p};

$shape = Compile::shape(
    div(
        h1(Slot::text('heading')),
        p(Slot::text('lead'))
    )->class('container')
);
```

槽位类型：

| 槽位 | 绑定 | 是否创建嵌套作用域 |
| --- | --- | --- |
| `Slot::text()` | 可字符串化的值，会转义 | 否 |
| `Slot::attr()` | 属性值，会转义 | 否 |
| `Slot::raw()` | 可字符串化的值（或这类值的可迭代集合），原样输出 | 否 |
| `Slot::child()` | 数组 | 是 |
| `Slot::each()` | 数组的可迭代集合 | 是，逐项 |
| `Slot::if()` | 真值条件 | 否（各分支共享作用域） |
| `Slot::eachKind()` | 带判别键的数组的可迭代集合 | 是，逐项 |

槽位名字始终是**数据键**（也是错误路径），而不是标签名或属性名：在 `a(Slot::text('label'))->class(Slot::attr('classList'))` 中，文本绑定 `label`，class 属性绑定 `classList`，而 `a` 与 `class` 来自树本身。

## 编译

`Compile::shape()` 把一棵树包装为 `Shape`。首次渲染会将它编译成扁平的 PHP 闭包：静态标记变成字面量字符串，转义只做一次，运行时只剩槽位的工作。

```php
<?php

$shape([
    'heading' => 'Welcome',
    'lead' => 'Compiled rendering',
]);
```

关键特性：

- **每个进程只编译一次**——把形状记忆化到组件函数内的 `static` 变量中，绝不要在请求处理器中构建形状。标准 PHP-FPM 下 `static` 每个请求都会重置，因此请启用 `Compile::cachePath()`，让请求加载已编译的渲染器而不是重新生成。
- **输出逐字节一致**——编译路径与 `render()` 共用同一份转义实现。
- **可选的磁盘缓存**——`Compile::cachePath($dir)` 会存储编译后的渲染器，让已预热的 worker 直接加载代码而不是生成代码。

`Shape::id()` 是结构性指纹（标签、属性、槽位与嵌套形状），无需编译即可获得；它决定磁盘缓存
的文件名，预编译产物也会记录它，`pure compile --check` 据此识别过期产物。组件的解析依据是
注册名或单元文件，而不是它。

## 数据绑定与作用域

渲染形状时绑定普通数据：

```php
<?php

$list = Compile::shape(ul(Slot::each('items', li(Slot::text('title')))));

$list(['items' => [['title' => 'a'], ['title' => 'b']]]);
```

`Slot::child()` 与 `Slot::each()` 会建立嵌套作用域，因此在 `li` 内部，槽位 `title` 针对当前项解析。缺失必填键会抛出带完整路径的 `Pure\Core\MissingSlotException`（`slot 'items[].title' is required but was not provided.`）；可选数据请使用 `->default($value)` 或 `->required(false)`。

## 组件

组件是一个 `*.cmp.php` 单元：带类型化参数、返回 `Raw` 的函数，加上紧挨着注册的惰性模板
工厂：

```php
<?php

// components/Card.cmp.php
use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h2(Slot::text('title')),
        p(Slot::text('content'))
    )->class(Slot::attr('class'))
));

function Card(string $title, string $content, string $class = 'card'): Raw
{
    return render('Card', title: $title, content: $content, class: $class);
}
```

组合方式见[组件](/zh/guide/components)，产物、缓存与每请求守卫见[编译组件](/zh/guide/compiled)。

## 状态管理

状态就是普通 PHP：值作为数据传入形状。

### 简单状态

```php
<?php

use Pure\Core\Raw;

use function Pure\HTML\{button, div, p};
use function Pure\Component\render;

function Counter(int $count): Raw
{
    return render('Counter', count: $count);
}

echo Counter(0);
```

### 全局状态

任何 PHP 存储方案都可以；组装好数据数组后渲染：

```php
<?php

class Store
{
    private static array $state = [];

    public static function set(string $key, mixed $value): void
    {
        self::$state[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        return self::$state[$key] ?? null;
    }
}

Store::set('user', ['name' => 'John']);
$user = Store::get('user');
```

## 下一步

- [编译组件](/zh/guide/compiled) - 生产环境的渲染路径
- [Props 与槽位](/zh/guide/props) - 数据如何绑定到形状
- [基本用法](/zh/guide/basic-usage) - 代码片段所用的标签 API
