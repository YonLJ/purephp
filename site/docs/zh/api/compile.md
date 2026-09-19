# 编译渲染

`Pure\Compile\Compile` 把一棵不含数据的**形状**树编译成扁平的 PHP 渲染器。
静态标记在编译期一次性转义，并作为字面量字符串块输出，因此渲染一个页面只比字符串拼接加上动态值转义略多一点开销。

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, li, p, ul};

// 每个进程构建 + 编译一次
$item  = Compile::shape(li(Slot::value('title')));
$shape = Compile::shape(
    div(
        h1(Slot::value('heading')),
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
| `Pure\Compile\Renderer` | 编译后的渲染器：`render($data)`、`save($path, $data, $header = '')`，以及只读属性 `source` / `id` |
| `Pure\Core\Slot` | 占位符构造器（`value`、`raw`、`child`、`each`、`if`）与修饰符 |
| `Pure\Core\MissingSlotException` | 必填槽位缺失时抛出，携带完整路径 |

## 函数组件
组件单元把惰性模板工厂注册到一个名字下，紧挨着的组件函数渲染这个名字。
`Pure\Component\render()` 在一个表达式里返回渲染好的 string：

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn () =>
    div(h2(Slot::value('title')), p(Slot::value('content')))->class('card')
);

function Card(string $title, string $content): string
{
    return render('Card', title: $title, content: $content);
}
```

| 函数 | 行为 |
| --- | --- |
| `register(string $name, string $file, Closure $factory, bool $override = false): void` | 注册组件单元；工厂必须惰性，可返回标签树或 `Shape` |
| `render(string $source, mixed ...$data): string` | 按名渲染单元或按路径渲染模板；绑定器带缓存 |

`render()` 按原样输出树，**不带文档头**；整份文档的文档头由调用方
自己拼接（`$root->documentHeader()`，或字面量 `<!DOCTYPE html>` /
`<?xml version="1.0"?>`）。

`render()` 的槽位值按名字传入（`render('Card', title: $title)`），也可以传解包的字符串键
数组；位置参数会被 `RuntimeException` 拒绝。需要自己持有或传递绑定器时，用
`Registry::component($source)`，它返回 `Closure(array $data): string`。

传入字符串时视为注册名、`*.cmp.php` 单元路径或 `*.shape.php` 模板
路径。同名注册到另一个文件、或同一文件注册另一个名字都会抛异常，除非传 `override: true`；
一个单元文件只注册一个组件。名字与它单元文件的路径解析到同一个绑定器。

对文件而言，相邻的 `*.pure.php` 产物存在且不早于单元/shape 文件时直接加载，生产环境因此
跳过工厂调用与形状树构建；否则调用工厂（每个编译 generation 一次）或编译 shape 文件
（磁盘缓存仍然生效）。文件缺失、模板未返回标签树或 `Shape`、产物未返回 `Renderer` 都会抛出带文件名
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
| `function Card(array $props): HTML` | `function Card(string $title): string` 加一个 `Card.cmp.php` 单元（函数 + 模板） |
| `h2($title)` | `h2(Slot::value('title'))` |
| `->class($classList)` | 静态值用 `->class($classList)`，动态值用 `->class(Slot::value('classList'))` |
| `array_map(fn ($row) => Row($row), $rows)` | 在组件函数里循环，把拼接好的标记经 `Slot::raw()` 注入 |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | 调用 `Child(...)`，把返回的标记经 `Slot::raw()` 注入 |

子组件的标记就是普通字符串，因此要经 raw 槽位进入模板——直接作为字符串子节点会被转义成文本：

```php
$shape = Compile::shape(div(Slot::raw('header'), Slot::each('rows', $row))->class('page'));
$shape(['header' => Header(), 'rows' => $rows]);
```

## 槽位类型

| 构造器 | 值 | 行为 |
| --- | --- | --- |
| `Slot::value($name)` | 可字符串化或 `null` | 位置决定语义：子节点位转字符串后转义（`null` 渲染为空内容，`true` 为 "1"）；属性位遵循 `setAttr()`（`true` 渲染 `name="name"`，`false`/`null` 省略该属性） |
| `Slot::raw($name)` | 可字符串化值、`null`，或这类值的可迭代集合 | 原样输出，绝不转义；集合按顺序拼接 |
| `Slot::child($name, $shape)` | 数组 | 作为 `$shape` 的嵌套数据作用域 |
| `Slot::each($name, $shape)` | 数组的可迭代集合 | 为每个 item 渲染一次 `$shape` |
| `Slot::if($name, $then, $else = null)` | 真值判断 | `$data[$name]` 为真值时渲染 `$then`，否则渲染 `$else`；键缺失视为 false，绝不抛异常 |

修饰符：

- `->required(false)`——允许槽位缺失。
- `->default($value)`——键缺失时使用的回退值。
- `Slot::if()` 会以 `LogicException` 拒绝这两个修饰符。
- 嵌套作用域直接读取 `$data[$name]`，数据形状由调用方在渲染前准备好。

值槽位与 raw 槽位必须可字符串化：接受 `null`、标量和 `Stringable`（含 `Raw`）；其它对象
抛出 `InvalidArgumentException`，错误信息中会指出完整槽位路径。
`raw` 槽位额外接受这类值的可迭代集合并原样拼接；某个元素不可字符串化时，报错会带上下标，
例如 `slot 'items[2]' must be stringable`。

选择列表槽位看标记是否已渲染：`raw()` 直接拼接已渲染好的标记（传渲染好的字符串、单个
`Raw` 或它们的列表），`each()` 则是数据驱动、逐项用自己的 shape 渲染。

## 静态子树折叠

不含任何槽位的子树就是静态标记。编译器在编译期把它渲染一次并折叠成单个字面量，
因此这类子树在渲染期没有任何开销：

```php
$shape = Compile::shape(div(
    Slot::raw('header'),
    div('Static footer')->class('footer'),
    Slot::value('title')
));
```

`div('Static footer')` 会被折叠成字面量；`header` 与 `title` 保持动态，已渲染好的
`Header()` 标记则在渲染时经 raw 槽位进入。

## 结构指纹

`Shape::id()` 是形状结构的 sha1 指纹：标签名、属性名与属性值、槽位种类与名称、默认值、
嵌套形状以及库缓存版本。它无需编译即可计算，并被用作磁盘渲染器缓存的键：生成的源码以它为
键存放，因此结构任何一处不同的两个形状不可能共用同一个缓存的渲染器。`*.pure.php` 产物会在
源码旁记下它，`pure compile --check` 正是据此识别过期产物。它并不是 `Pure\Component\render()`
解析组件所依据的东西——那是注册名或单元文件——而且只有*数据*变化时它不会改变。真正用得上它
的是那种为每个变体组装不同形状的应用：指纹就是存放它们的记忆表的一个廉价而稳定的键：

```php
$shapes[$classList . '|' . $item->id()] ??= Compile::shape(...);
```

## 编译产物 API

```php
$compiled = $shape->compile();

$compiled->render($data);                    // 返回 string
$compiled->save($path, $data);               // 写入文件，返回写入的字节数
$compiled->save($path, $data, $header);      // 在文件开头补上 $header
$compiled->source;                           // 生成的 PHP 源码（预编译产物为空）
$compiled->id;                               // 结构指纹（与 Shape::id() 相同）
```

`Shape::save($path, $data)` 是面向用户的便捷方法：写出渲染结果，并补上根标签的文档声明
（例如 `<!DOCTYPE html>` 或 XML 声明），除非你传入自己的声明。
`Renderer::save()` 则把声明作为可选的第三个参数，默认为空，因此需要整份文档的处理器
自己拼接：`'<!DOCTYPE html>' . $renderer->render($data)`。

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
- 位置错误（raw 槽用作属性值）或缺少形状：编译期抛 `LogicException`。
- 列表不可迭代、item 或作用域不是数组、值不可字符串化：渲染期抛
  `InvalidArgumentException`（对 `Slot::if()` 使用 `required()` / `default()` 会抛
  `LogicException`）。

## 含槽位的树不能使用其它输出路径

含槽位的树调用 `render()`、`print()` 和 `save()` 会抛出 `LogicException`，因为
没有可绑定的数据。`toJSON()` 把槽位描述为 `['slot' => 'name']`。

## 性能

`bench/compare.php` 量的是 604 元素页面上的各条路径，`examples/bootstrap/bench.php` 量的则是
一个由组件函数组合起来的真实页面；实测行见 `bench/README.md`，[编译组件指南](/zh/guide/compiled#性能)
说明各项开销分别在什么时候占主导。绝对数值会随 PHP 版本、opcache 与 CPU 变化，因此对照之前
请先自己跑一遍：

```bash
php bench/compare.php
php examples/bootstrap/bench.php
```

## 限制

- 标签名不能依赖数据：一个形状始终使用相同的标签。结构变化请用 `Slot::if()`，
  或者在渲染前规整数据。
- 形状只在 PHP 进程的生命周期内存在。长驻 worker（或 `opcache.preload`）下是每个
  worker 一次；标准 PHP-FPM 下形状树会在每个请求中重建、渲染器会被重新生成，反而比
  `render()` 更慢。请启用 `cachePath()`，让请求加载生成的渲染器而不是重新生成。
- 编译渲染拿编译成本换速度：为每进程只渲染一次的形状做编译比 `render()` 更慢。请编译
  会被反复渲染的页面和组件。
