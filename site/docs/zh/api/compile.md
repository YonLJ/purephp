# 编译渲染

`Pure\Compile\Compile` 把一棵不含数据的**形状**树编译成扁平的 PHP 渲染器。
静态标记在编译期一次性转义，并作为字面量字符串块输出，因此渲染一个页面只比字符串拼接加上动态值转义略多一点开销。

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, li, p, ul};

// 每个进程构建 + 编译一次
$item  = Compile::shape(li(Slot::text('title')));
$shape = Compile::shape(
    div(
        h1(Slot::text('heading')),
        ul(Slot::each('items', $item))
    )->class('card')
);

// 每个请求用普通数据渲染
echo $shape([
    'heading' => 'Users',
    'items' => [['title' => 'Ada'], ['title' => 'Grace']],
]);
```

对同一棵树，输出与 `Tag::render()` **逐字节一致**，因为两条路径共用同一份转义实现
（`Pure\Core\Escaper`，`@internal`）。

## 类

| 类 | 用途 |
| --- | --- |
| `Pure\Compile\Compile` | 门面：`shape()`、`cachePath()`、`clearCache()`、`flush()`、`guard()` |
| `Pure\Compile\Shape` | 不含数据的树：`__invoke($data)`、`compile()`、`id()`、`print($data)`、`save($path, $data)` |
| `Pure\Compile\Renderer` | 编译后的渲染器：`render($data)`、`save($path, $data, $header = null)`，以及只读属性 `source` / `id` |
| `Pure\Core\Slot` | 占位符构造器（`text`、`attr`、`raw`、`child`、`each`、`if`、`eachKind`）与修饰符 |
| `Pure\Core\MissingSlotException` | 必填槽位缺失时抛出，携带完整路径 |

## 函数组件

组件单元把惰性模板工厂注册到一个名字下，紧挨着的组件函数渲染这个名字。
`Pure\Component\render()` 与 `renderPage()` 在一个表达式里把注册的模板变成 `Raw` 标记：

```php
<?php

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn (): Shape => Compile::shape(
    div(h2(Slot::text('title')), p(Slot::text('content')))->class('card')
));

function Card(string $title, string $content): Raw
{
    return render('Card', title: $title, content: $content);
}
```

| 函数 | 行为 |
| --- | --- |
| `register(string $name, string $file, Closure $factory, bool $override = false): void` | 注册组件单元；工厂必须惰性且返回 `Shape` |
| `registerPage(string $name, string $file, Closure $factory, bool $override = false): void` | 同上，并附加根标签的文档声明 |
| `render(string $source, mixed ...$data): Raw` | 按名渲染单元或按路径渲染模板；绑定器带缓存 |
| `renderPage(string $source, array $data): Raw` | 同上，并附加根标签的文档声明 |
| `component(Tag\|string $source): Closure` | 返回片段的 `fn (array $data): Raw` 绑定器 |
| `page(Tag\|string $source): Closure` | 同上，并附加根标签的文档声明 |

`render()` 的槽位值按名字传入（`render('Card', title: $title)`），也可以传解包的字符串键
数组；位置参数会被 `RuntimeException` 拒绝。`component()` 与 `page()` 是更底层的助手，
用于内联树，或需要自己把绑定器存进 `static` 变量的场合。

传入 `Tag` 时就地编译；传入字符串时视为注册名、`*.cmp.php` 单元路径或 `*.shape.php` 模板
路径。同名注册到另一个文件、或同一文件注册另一个名字都会抛异常，除非传 `override: true`；
一个单元文件只注册一个组件。名字与它单元文件的路径解析到同一个绑定器。

对文件而言，相邻的 `*.pure.php` 产物存在且不早于单元/shape 文件时直接加载，生产环境因此
跳过工厂调用与形状树构建；否则调用工厂（每个编译 generation 一次）或编译 shape 文件
（磁盘缓存仍然生效）。文件缺失、模板未返回 `Shape`、产物未返回 `Renderer` 都会抛出带文件名
的 `RuntimeException`。用 `pure compile` 为所有 `*.shape.php` 与 `*.cmp.php` 构建产物，
用 `pure compile --list` 打印发现的单元，用 `pure compile --check` 在 CI 中保证产物新鲜。

## 形状与数据

形状就是普通标签树，只是把动态值替换为 `Slot` 占位符。形状里不能包含请求数据，并且必须
**每进程只构建一次**——文件形式由 `render()` 的路径缓存保证，内联树放进组件函数内的
`static` 变量中，绝不能放在请求处理器里。
标准 PHP-FPM 下 `static` 每个请求都会重置，因此请启用 `Compile::cachePath()`，让请求加载
已编译的渲染器而不是重新生成。

| 经典组件 | PurePHP 组件 |
| --- | --- |
| `function Card(array $props): HTML` | `function Card(string $title): Raw` 加一个 `Card.cmp.php` 单元（函数 + 模板） |
| `h2($title)` | `h2(Slot::text('title'))` |
| `->class($classList)` | 静态值用 `->class($classList)`，动态值用 `->class(Slot::attr('classList'))` |
| `array_map(fn ($row) => Row($row), $rows)` | 在组件函数里循环，把拼接好的标记经 `Slot::raw()` 注入 |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | 调用 `Child(...)` 并把它的 `Raw` 经 `Slot::raw()` 注入 |

没有 props 的子组件也可以直接作为子节点传给模板：`Raw` 是合法的标签内容，该子树会被
编译成字面量。

```php
$shape = Compile::shape(div(Header(), Slot::each('rows', $row))->class('page'));
```

## 槽位类型

| 构造器 | 值 | 行为 |
| --- | --- | --- |
| `Slot::text($name)` | 可字符串化或 `null` | 转换为字符串后转义；`null` 渲染为空内容 |
| `Slot::attr($name)` | 可字符串化或 `null` | 转义后的属性值；`null` 时省略该属性（与 `setAttr(null)` 相同） |
| `Slot::raw($name)` | 可字符串化或 `null` | 原样输出，绝不转义 |
| `Slot::child($name, $shape)` | 数组 | 作为 `$shape` 的嵌套数据作用域 |
| `Slot::each($name, $shape)` | 数组的可迭代集合 | 为每个 item 渲染一次 `$shape` |
| `Slot::if($name, $then, $else = null)` | 真值判断 | `$data[$name]` 为真值时渲染 `$then`，否则渲染 `$else`；键缺失视为 false，绝不抛异常 |
| `Slot::eachKind($name, ['kind' => $shape], $kindKey = 'kind')` | 数组的可迭代集合 | 按 `$item[$kindKey]` 分发每个 item；未知 kind 抛出 `InvalidArgumentException` |

修饰符：

- `->required(false)`——允许槽位缺失。
- `->default($value)`——键缺失时使用的回退值。
- `Slot::if()` 会以 `LogicException` 拒绝这两个修饰符。
- 嵌套作用域直接读取 `$data[$name]`，数据形状由调用方在渲染前准备好。

值必须可字符串化：接受 `null`、标量和 `Stringable`；数组或其它对象会抛出
`InvalidArgumentException`，错误信息中会指出完整槽位路径。

## 静态子树折叠

不含任何槽位的子树就是静态标记。编译器在编译期把它渲染一次并折叠成单个字面量，
因此这类子树在渲染期没有任何开销：

```php
$shape = Compile::shape(div(Header(), Slot::text('title')));
```

`Header()` 作为字面量输出；只有 `title` 保持动态。

## 结构指纹

`Shape::id()` 是形状结构的 sha1 结构指纹：标签名、属性、槽位种类与名称、默认值、
嵌套形状以及库缓存版本。它无需编译即可计算，用作缓存文件名和组件缓存键：

```php
$shapes[$classList . '|' . $item->id()] ??= Compile::shape(...);
```

## 编译产物 API

```php
$compiled = $shape->compile();

$compiled->render($data);         // 返回 string
$compiled->save($path, $data);    // 写入文件，返回写入的字节数
$compiled->source;                // 生成的 PHP 源码（预编译产物为空）
$compiled->header;                // 编译期捕获的文档声明
$compiled->id;                    // 结构指纹（与 Shape::id() 相同）
```

`Shape::save($path, $data)` 是面向用户的便捷方法：写出渲染结果，并补上根标签的文档声明
（例如 `<!DOCTYPE html>` 或 XML 声明），除非你传入自己的声明。
`Renderer::save()` 在 `$header` 为 null 时使用编译期捕获的声明——运行时编译的渲染器为空，
[预编译产物](/zh/guide/compiled#预编译产物) 则为根标签的声明。`Renderer::$header`
暴露该声明，因此处理器可以直接输出完整文档：`$renderer->header . $renderer->render($data)`。

## 磁盘缓存

默认关闭。在引导阶段启用一次：

```php
use Pure\Compile\Compile;

Compile::cachePath(__DIR__ . '/var/cache/purephp');
```

- 缓存文件以 `Shape::id()` 命名，原子写入（临时文件 + rename），内容是返回编译闭包的
  纯 PHP，因此 opcache 可以直接提供它们。
- 缓存条目的头部与预期的 id、缓存版本或 PHP 版本不匹配时，该条目会被丢弃并重新生成。
- `Compile::clearCache()` 删除由本库写入的缓存文件。
- 生成的源码也会按指纹在内存中记忆化，因此在同一进程内重建同一棵树会重新求值缓存的
  源码，而不是重新生成。这份记忆有
  字节预算上限（超限时先丢弃最旧的源码，比预算还大的单个源码不会被保留），因此随请求
  变化的结构不会让它无限增长。可通过环境变量 `PURE_COMPILE_MEMO_BYTES` 调整预算
  （设为 `0` 即关闭记忆化）。
- `Compile::flush()` 让内存中的渲染器失效（每个形状在下次使用时重新编译）；它不会删除
  缓存文件。

缓存目录必须是私有目录：归 PHP 运行用户所有、组与其他用户不可写（`cachePath()` 会以
0700 创建缺失目录，并拒绝权限过松或属主不符的目录），且应位于 Web 根目录之外。不要把
缓存目录直接指向 `/tmp` 这类共享位置。只有在想强制重新生成时才在部署之间删除它。

## 每请求守卫

每请求编译形状比渲染已编译的渲染器更慢。启用开发守卫来检测这种情况：

```php
Compile::guard(true); // 或设置 PURE_COMPILE_GUARD=1
```

当同一调用点在单个进程内调用 `Compile::shape()` 超过 20 次时，会触发
`E_USER_WARNING`，建议改用 `static $shape ??=` 模式。

## 错误

- 必填槽位缺失：`Pure\Core\MissingSlotException`，带完整路径，例如
  `slot 'items[].title' is required but was not provided.`
- 位置错误（`Slot::attr` 用作子节点、`Slot::text` 用作属性值）或缺少形状：编译期抛
  `LogicException`。
- `Slot::eachKind()` 没有分支形状，或判别键为空、数字形：构建期抛
  `InvalidArgumentException`（对 `Slot::if()` 使用 `required()` / `default()` 会抛
  `LogicException`）。
- 列表不可迭代、item 或作用域不是数组、item 的判别键缺失或未知、值不可字符串化：
  渲染期抛 `InvalidArgumentException`。

## 含槽位的树不能使用其它输出路径

含槽位的树调用 `render()`、`print()` 和 `save()` 会抛出 `LogicException`，因为
没有可绑定的数据。`toJSON()` 把槽位描述为 `['slot' => 'name']`。

## 性能

在 PHP 8.4 上实测（604 个元素的页面，200 行数据；可用 `php bench/compare.php` 复现）：

| 路径 | 每渲染耗时 |
| --- | --- |
| 构建树 + `render()` | ~700–750 µs |
| 仅渲染（复用同一棵树） | ~220–230 µs |
| 编译形状 + 数据 | ~120 µs |
| 编译静态树（字面量） | < 1 µs |

bootstrap features 示例使用编译路径后渲染约快 10 倍。

基准测试为手动运行，不属于 CI：

```bash
php bench/compare.php
php examples/bootstrap/bench.php
```

## 限制

- 标签名不能依赖数据：一个形状始终使用相同的标签。结构变化请用 `Slot::if()` /
  `Slot::eachKind()`，或者在渲染前规整数据。
- 形状只在 PHP 进程的生命周期内存在。长驻 worker（或 `opcache.preload`）下是每个
  worker 一次；标准 PHP-FPM 下形状树会在每个请求中重建、渲染器会被重新生成，反而比
  `render()` 更慢。请启用 `cachePath()`，让请求加载生成的渲染器而不是重新生成。
- 编译渲染拿编译成本换速度：为每进程只渲染一次的形状做编译比 `render()` 更慢。请编译
  会被反复渲染的页面和组件。
