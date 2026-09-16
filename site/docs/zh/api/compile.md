# 编译渲染

`Pure\Compile\Compile` 把一棵**不含数据**的形状（shape）树编译成扁平 PHP 渲染器。
静态标记在编译期一次性转义并变成字面量字符串，因此渲染一页只比字符串拼接 + 动态值转义多一点点开销。

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\li;
use function Pure\HTML\p;
use function Pure\HTML\span;
use function Pure\HTML\ul;

// 每个进程构建 + 编译一次
$item  = Compile::shape(li(Slot::text('title')));
$shape = Compile::shape(
    div(
        h1(Slot::text('heading')),
        ul(Slot::each('items', $item))
    )->class('card')
);

// 每个请求只绑定数据
echo $shape([
    'heading' => 'Users',
    'items' => [['title' => 'Ada'], ['title' => 'Grace']],
]);
```

同一棵树编译渲染的输出与 `Tag::render()` **逐字节一致**：两条路径共用同一份转义实现
（`Pure\Core\Escaper`，`@internal`）。

## 形状与数据

形状就是普通标签树，只是把动态值换成 `Slot` 占位符。形状里不能带请求数据，并且必须
**每进程只构建一次**——放进组件函数的 `static` 变量里，绝不能放在请求处理逻辑中。

| 经典组件写法 | 编译组件写法 |
| --- | --- |
| `function Card(array $props): HTML` | `function CardShape(): Shape` |
| `h2($title)` | `h2(Slot::text('title'))` |
| `->class($classList)` | 静态 props 直接 `->class($classList)`，动态的用 `->class(Slot::attr('classList'))` |
| `array_map(fn ($row) => Row($row), $rows)` | `Slot::each('rows', RowShape())` |
| 嵌入子组件 | `Slot::sub('child', ChildShape())` 或组件映射 |

无数据的静态子组件不需要槽位——直接放进形状，会被编译成字面量：

```php
$shape = Compile::shape(div(Header(), Slot::each('rows', $row))->class('page'));
```

## 槽位类型

| 构造 | 值 | 行为 |
| --- | --- | --- |
| `Slot::text($name)` | 可字符串化或 `null` | 转字符串后转义；`null` 渲染为空内容 |
| `Slot::attr($name)` | 可字符串化或 `null` | 转义后的属性值；`null` 时省略该属性（与 `setAttr(null)` 一致） |
| `Slot::raw($name)` | 可字符串化或 `null` | 原样输出，不转义 |
| `Slot::sub($name, $shape)` | 数组 | 作为 `$shape` 的嵌套数据作用域 |
| `Slot::each($name, $shape)` | 数组的可迭代集合 | 每个 item 渲染一次 `$shape` |

修饰符：

- `->required(false)`：允许缺失。
- `->default($value)`：键缺失时的回退值。
- `Slot::sub($name, $shape, $map)` / `Slot::each($name, $shape, $map)`：用闭包派生嵌套作用域，
  而不是读取 `$data[$name]`；组件把自身 props 映射给子组件时用它，例如
  `fn (array $d) => ['href' => '#' . $d['icon']]`。

槽位值必须可字符串化：`null`、标量与 `Stringable` 都可以；数组或其它对象会抛出
`InvalidArgumentException`，错误信息包含完整槽位路径。

## 错误

- 必填槽位缺失：`Pure\Core\MissingSlotException`，带完整路径，例如
  `slot 'items[].title' is required but was not provided.`
- 位置错误（`Slot::attr` 放子节点位、`Slot::text` 放属性位）或缺少形状：编译期抛 `LogicException`。
- 列表值不可迭代、sub 作用域不是数组：渲染期抛 `InvalidArgumentException`。

## 编译产物 API

```php
$compiled = $shape->compile();

$compiled($data);                 // 返回 string
$compiled->print($data);          // 直接 echo
$compiled->save($path, $data);    // 写入文件，返回写入字节数
$compiled->source();              // 生成的 PHP 源码，便于排查
$compiled->id();                  // 生成源码的稳定 sha1
```

当组件形状依赖子形状时，`$shape->id()` 适合作为缓存键。

## 含槽位的树不能用其它出口

含槽位的树调用 `render()`、`toPrint()`、`toSave()` 会抛 `LogicException`（没有可绑定
的数据）；`toJSON()` 会把槽位描述为 `['slot' => 'name']`。

## 性能

PHP 8.1 实测（603 元素页面、200 行数据）：

| 路径 | 每渲染耗时 |
| --- | --- |
| 构建树 + `render()` | ~950 µs |
| 编译形状 + 数据 | ~235 µs |
| 编译静态树（纯字面量） | < 1 µs |

bootstrap features 示例中 `app.php`（经典路径）与 `app-compiled.php`（编译路径）输出逐字节一致，
编译路径**快 6.9×**（`php examples/bootstrap-features/bench.php`）。

## 限制

- 暂不支持数据驱动的**结构**变化（条件、层级变化）；可用 `Slot::each` + map 规整数据，或回退到经典
  `render()` 路径。
- 形状是进程级产物：PHP-FPM 下每个 worker 进程会重建并重新编译一次，相对每请求的收益可以忽略。
- 编译是拿"编译成本"换"渲染速度"：只渲染一次的树直接 `render()` 更快；请对反复渲染的页面/组件使用编译渲染。
