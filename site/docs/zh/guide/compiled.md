# 编译组件

编译渲染把不含数据的**形状**转换为扁平的 PHP 渲染器。静态标记在编译期只转义一次，并作为字面量字符串输出，因此渲染页面的开销仅比字符串拼接加上动态值转义略高——与编译型模板引擎持平。

形状**每个进程只构建一次**——长驻 worker、预加载或 CLI 进程，或任何在请求之间保留 PHP
状态的运行时。标准 PHP-FPM 下每个请求都是全新的，因此请启用磁盘缓存（见[缓存](#缓存)），
让请求加载已编译的渲染器而不是逐请求重新生成。

## 形状、槽位、Renderer

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, li, ul};

// 形状就是普通标签树，只是把数据换成 Slot 占位符。
$item = Compile::shape(li(Slot::text('title')));

$page = Compile::shape(
    div(
        h1(Slot::text('heading')),
        ul(Slot::each('items', $item))
    )->class('card')
);

// 渲染只负责绑定普通数据。
echo $page([
    'heading' => 'Users',
    'items' => [['title' => 'Ada'], ['title' => 'Grace']],
]);
```

对同一棵树，输出与 `Tag::render()` **逐字节一致**，因为两条路径共用同一份转义实现。

| 对象 | 含义 |
| --- | --- |
| `Shape` | 不含数据的树；`__invoke($data)`、`compile()`、`id()`、`print($data)` |
| `Renderer` | 编译后的渲染器；`__invoke($data)`、`print()`、`save()`、`source()`、`id()` |
| `Slot` | 数据的占位符，在渲染时绑定 |

## 槽位类型

| 构造器 | 值 | 行为 |
| --- | --- | --- |
| `Slot::text($name)` | 可字符串化或 `null` | 转字符串后转义；`null` 渲染为空内容 |
| `Slot::attr($name)` | 可字符串化或 `null` | 转义后的属性值；`null` 省略该属性（与 `setAttr(null)` 一致） |
| `Slot::raw($name)` | 可字符串化或 `null` | 原样输出，绝不转义 |
| `Slot::sub($name, $shape)` | 数组 | 作为 `$shape` 的嵌套数据作用域 |
| `Slot::each($name, $shape)` | 数组的可迭代集合 | 为每个项渲染 `$shape` |
| `Slot::if($name, $then, $else = null)` | 真值判断 | 当 `$data[$name]` 为真时渲染 `$then`，否则渲染 `$else`；缺失的键为 false，且绝不抛出异常 |
| `Slot::eachAny($name, ['kind' => $shape, ...])` | 数组的可迭代集合 | 按 `$item['kind']` 逐项分派；未知的 kind 会抛出 `InvalidArgumentException` |

修饰符：

- `->required(false)`——槽位可以缺失。
- `->default($value)`——键缺失时使用的回退值。
- `Slot::if()` 会以 `LogicException` 拒绝这两个修饰符。
- `Slot::sub(..., $map)` / `Slot::each(..., $map)` / `Slot::eachAny(..., $map)`——用闭包派生嵌套作用域，而不是读取 `$data[$name]`；组件正是通过这种方式把自己的 props 映射给子组件。

值转换：文本/属性/raw 槽位接受 `null`、标量与 `Stringable`；数组和其他对象会抛出 `InvalidArgumentException`，并在信息中给出完整槽位路径。

## 作用域与缺失数据

`Slot::sub()` 与 `Slot::each()` 会创建嵌套数据作用域；在其中，槽位针对该作用域解析。缺失必填键会抛出带完整路径的 `Pure\Core\MissingSlotException`，例如 `slot 'items[].title' is required but was not provided.`。可选数据请使用 `default()` 或 `required(false)`。

`Slot::if()` 与 `Slot::eachAny()` 的分支共享当前作用域，因此下面这样写可以自然工作：

```php
$item = Compile::shape(
    li(
        Slot::text('name'),
        Slot::if('admin', Compile::shape(span('(admin)')))
    )
);
```

## 组件

组件是返回 `Shape` 的函数。静态 props 是函数参数，动态 props 是槽位，并且形状会记忆化到 `static` 中，因此每个进程只编译一次（PHP-FPM 场景见[缓存](#缓存)）：

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, p};

function CardShape(string $classList = 'card'): Shape
{
    static $shapes = [];

    return $shapes[$classList] ??= Compile::shape(
        div(
            h2(Slot::text('title')),
            p(Slot::text('content'))
        )->class($classList)
    );
}

function PageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            Slot::sub('card', CardShape('card shadow'))
        )->class('container')
    );
}

PageShape()->print([
    'card' => ['title' => 'Hello', 'content' => 'Compiled card'],
]);
```

嵌套组件使用 `Slot::sub()`，列表使用 `Slot::each()`，混合列表使用 `Slot::eachAny()`，可选/条件标记使用 `Slot::if()`。

### 列表

```php
$row = Compile::shape(li(Slot::text('label')));

$shape = Compile::shape(ul(Slot::each('rows', $row)));
$shape(['rows' => [['label' => 'a'], ['label' => 'b']]]);
```

### 异构列表

```php
$text = Compile::shape(p(Slot::text('value')));
$link = Compile::shape(a(Slot::text('value'))->href(Slot::attr('href')));

$shape = Compile::shape(div(Slot::eachAny('blocks', [
    'text' => $text,
    'link' => $link,
])));

$shape(['blocks' => [
    ['kind' => 'text', 'value' => 'hello'],
    ['kind' => 'link', 'value' => 'docs', 'href' => '/docs'],
]]);
```

每个项都必须是带有判别键的数组（默认是 `kind`；可以把不同的键作为 `Slot::eachAny()` 的第三个参数传入）。

## 缓存

默认情况下，编译后的渲染器只存在于内存中，这在请求之间保留状态的长驻 worker 中收益最大。标准 PHP-FPM 下，形状树会在每个请求中重建、渲染器会被重新生成——这比即时渲染更慢——因此请启用磁盘渲染器缓存，直接加载生成的代码而不是重新生成：

```php
use Pure\Compile\Compile;

// 在引导阶段执行一次
Compile::cachePath(__DIR__ . '/var/cache/purephp');
```

- 缓存文件以 `Shape::id()` 为内容寻址；形状变化会生成新文件。
- 写入是原子的（临时文件 + 重命名），因此并发 worker 是安全的。
- 缓存文件是普通 PHP，对 opcache 友好。目录必须是私有目录：归 PHP 运行用户所有、
  组与其他用户不可写（缺失时以 0700 创建）、并位于 Web 根目录之外——`cachePath()`
  会拒绝权限过松或属主不符的目录；不要把缓存目录直接指向 `/tmp` 这类共享位置。
- `Compile::clearCache()` 会删除由本库写入的文件。
- `Compile::flush()` 会使内存中的渲染器失效（在部署后的长驻 worker 中很有用）。

要发现每个请求都重新构建（而不是被记忆化）的形状，请启用开发守卫：

```php
Compile::guard(true);           // 或设置 PURE_COMPILE_GUARD=1
```

当同一个调用点在一个进程中调用 `Compile::shape()` 次数过多时，PHP 会发出 `E_USER_WARNING`，建议采用 `static $shape ??=` 模式。

## 性能

在 PHP 8.4 上实测（603 个元素的页面，200 行数据）：

| 路径 | 每次渲染耗时 |
| --- | --- |
| 构建树 + `render()` | ~200–650 µs |
| 编译形状 + 数据 | ~150 µs |
| 编译静态树（字面量） | < 1 µs |

bootstrap features 示例的渲染速度比经典的构建加渲染路径快约 8 倍。复现方式：

```bash
php bench/compare.php
php examples/bootstrap-features/bench.php
```

## 限制

- 标签名不能依赖数据：形状始终使用相同的标签。结构变化请使用 `Slot::if()` / `Slot::eachAny()`，或者在渲染前规范化数据。
- 编译后的代码与形状结构绑定；改变形状会改变它的 `id()`，从而改变其缓存文件。
- 映射闭包按文件与行号生成指纹；就地修改闭包体不会改变指纹。编辑映射闭包时请清除缓存（或提升 `Compile::CACHE_VERSION`）。
- 编译时会读取当前的形状树，`id()` 也反映调用时刻的树。已编译的渲染器会持续渲染它编译时的那份树，因此在修改已包装为形状的树之后需要调用 `Compile::flush()`；每个进程只构建一次形状即可完全避免此问题。
- 形状不得包含请求数据——它们是进程级产物。

## 经典组件 → 形状映射

| 经典组件 | 编译组件 |
| --- | --- |
| `function Card(array $props): HTML` | `function CardShape(): Shape` |
| `h2($title)` | `h2(Slot::text('title'))` |
| `->class($classList)` | 静态 props 直接 `->class($classList)`，动态的用 `->class(Slot::attr('classList'))` |
| `array_map(fn ($row) => Row($row), $rows)` | `Slot::each('rows', RowShape())` |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | `Slot::sub('child', ChildShape())` 或映射 |

即时（`render()`）标签树仍然可用于代码片段与调试；参见[基本用法](/zh/guide/basic-usage)。
