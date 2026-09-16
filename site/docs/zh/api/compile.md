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
| `Pure\Compile\Shape` | 不含数据的树：`__invoke($data)`、`compile()`、`id()`、`print($data)` |
| `Pure\Compile\Renderer` | 编译后的渲染器：`__invoke($data)`、`id()`、`source()`、`print($data)`、`save($path, $data, $header = '')` |
| `Pure\Core\Slot` | 占位符构造器（`text`、`attr`、`raw`、`sub`、`each`、`if`、`eachAny`）与修饰符 |
| `Pure\Core\MissingSlotException` | 必填槽位缺失时抛出，携带完整路径 |

## 形状与数据

形状就是普通标签树，只是把动态值替换为 `Slot` 占位符。形状里不能包含请求数据，并且必须
**每进程只构建一次**——放进组件函数内的 `static` 变量中，绝不能放在请求处理器里。
标准 PHP-FPM 下 `static` 每个请求都会重置，因此请启用 `Compile::cachePath()`，让请求加载
已编译的渲染器而不是重新生成。

| 经典组件 | 编译组件 |
| --- | --- |
| `function Card(array $props): HTML` | `function CardShape(): Shape` |
| `h2($title)` | `h2(Slot::text('title'))` |
| `->class($classList)` | 静态 props 用 `->class($classList)`，动态 props 用 `->class(Slot::attr('classList'))` |
| `array_map(fn ($row) => Row($row), $rows)` | `Slot::each('rows', RowShape())` |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | `Slot::sub('child', ChildShape())` 或组件映射 |

静态子组件完全不需要槽位——直接放进形状里构建，它们会被编译成字面量：

```php
$shape = Compile::shape(div(Header(), Slot::each('rows', $row))->class('page'));
```

## 槽位类型

| 构造器 | 值 | 行为 |
| --- | --- | --- |
| `Slot::text($name)` | 可字符串化或 `null` | 转换为字符串后转义；`null` 渲染为空内容 |
| `Slot::attr($name)` | 可字符串化或 `null` | 转义后的属性值；`null` 时省略该属性（与 `setAttr(null)` 相同） |
| `Slot::raw($name)` | 可字符串化或 `null` | 原样输出，绝不转义 |
| `Slot::sub($name, $shape)` | 数组 | 作为 `$shape` 的嵌套数据作用域 |
| `Slot::each($name, $shape)` | 数组的可迭代集合 | 为每个 item 渲染一次 `$shape` |
| `Slot::if($name, $then, $else = null)` | 真值判断 | `$data[$name]` 为真值时渲染 `$then`，否则渲染 `$else`；键缺失视为 false，绝不抛异常 |
| `Slot::eachAny($name, ['kind' => $shape], $kindKey = 'kind')` | 数组的可迭代集合 | 按 `$item[$kindKey]` 分发每个 item；未知 kind 抛出 `InvalidArgumentException` |

修饰符：

- `->required(false)`——允许槽位缺失。
- `->default($value)`——键缺失时使用的回退值。
- `Slot::if()` 会以 `LogicException` 拒绝这两个修饰符。
- `Slot::sub($name, $shape, $map)` / `Slot::each($name, $shape, $map)` /
  `Slot::eachAny(..., $map)`——用闭包派生嵌套作用域，而不是读取 `$data[$name]`；
  组件借此把自身 props 映射给子组件（例如 `fn (array $d) => ['href' => '#' . $d['icon']]`）。

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
嵌套形状、map 闭包（文件与行号）以及库缓存版本。它无需编译即可计算，用作缓存文件名
和组件缓存键：

```php
$shapes[$classList . '|' . $item->id()] ??= Compile::shape(...);
```

## 编译产物 API

```php
$compiled = $shape->compile();

$compiled($data);                 // 返回 string
$compiled->print($data);          // 直接 echo
$compiled->save($path, $data);    // 写入文件，返回写入的字节数
$compiled->source();              // 生成的 PHP 源码，调试时有用
$compiled->id();                  // 结构指纹（与 Shape::id() 相同）
```

## 磁盘缓存

默认关闭。在引导阶段启用一次：

```php
use Pure\Compile\Compile;

Compile::cachePath(__DIR__ . '/var/cache/purephp');
```

- 缓存文件以 `Shape::id()` 命名，原子写入（临时文件 + rename），内容是返回编译闭包的
  纯 PHP，因此 opcache 可以直接提供它们。
- 缓存条目的头部与预期的 id、map 数量、缓存版本或 PHP 版本不匹配时，该条目会被丢弃
  并重新生成。
- 引用组件映射的渲染器依然有效，因为 map 闭包保存在形状中；被缓存的只有生成的代码。
- `Compile::clearCache()` 删除由本库写入的缓存文件。
- `Compile::flush()` 让内存中的渲染器失效（每个形状在下次使用时重新编译）；它不会删除
  缓存文件。

缓存目录必须是私有目录：归 PHP 运行用户所有、组与其他用户不可写（`cachePath()` 会以
0700 创建缺失目录，并拒绝权限过松或属主不符的目录），且应位于 Web 根目录之外。不要把
缓存目录直接指向 `/tmp` 这类共享位置。只有在想强制重新生成时才在部署之间删除它；修改
map 闭包不会改变结构指纹，因此要么清空缓存，要么提升 `Compile::CACHE_VERSION`。

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
- 列表不可迭代、item 或作用域不是数组、未知的 `eachAny` kind、值不可字符串化：
  渲染期抛 `InvalidArgumentException`。

## 含槽位的树不能使用其它输出路径

含槽位的树调用 `render()`、`toPrint()` 和 `toSave()` 会抛出 `LogicException`，因为
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
php examples/bootstrap-features/bench.php
```

## 限制

- 标签名不能依赖数据：一个形状始终使用相同的标签。结构变化请用 `Slot::if()` /
  `Slot::eachAny()`，或者在渲染前规整数据。
- 形状只在 PHP 进程的生命周期内存在。长驻 worker（或 `opcache.preload`）下是每个
  worker 一次；标准 PHP-FPM 下形状树会在每个请求中重建、渲染器会被重新生成，反而比
  `render()` 更慢。请启用 `cachePath()`，让请求加载生成的渲染器而不是重新生成。
- 编译渲染拿编译成本换速度：为每进程只渲染一次的形状做编译比 `render()` 更慢。请编译
  会被反复渲染的页面和组件。
- map 闭包按文件和行号参与结构指纹；就地修改闭包体不会使缓存失效。
