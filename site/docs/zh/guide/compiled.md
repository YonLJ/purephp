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
$item = Compile::shape(li(Slot::value('title')));

$root = Compile::shape(
    div(
        h1(Slot::value('heading')),
        ul(Slot::each('items', $item))
    )->class('card')
);

// 渲染只负责绑定普通数据。
echo $root([
    'heading' => 'Users',
    'items' => [['title' => 'Ada'], ['title' => 'Grace']],
]);
```

对同一棵树，输出与 `Tag::render()` **逐字节一致**，因为两条路径共用同一份转义实现。

| 对象 | 含义 |
| --- | --- |
| `Shape` | 不含数据的树；`__invoke($data)`、`compile()`、`id()`、`print($data)`、`save($path, $data)` |
| `Renderer` | 编译后的渲染器；`render($data)`、`save($path, $data)`，以及只读属性 `source` / `id` / `slots` |
| `Slot` | 数据的占位符，在渲染时绑定 |

## 槽位类型

| 构造器 | 值 | 行为 |
| --- | --- | --- |
| `Slot::value($name)` | 标量 / `Stringable`；`null` 仅在可选槽或属性槽中可用 | 子节点位转成字符串并转义（`true`→"1"，必填槽拒绝 `null`）；属性位遵循 `setAttr()`（`true`→`name="name"`，`false`/`null` 省略） |
| `Slot::raw($name)` | 可字符串化值，或这类值的可迭代集合 | 原样输出，绝不转义；可迭代集合会逐元素转成字符串后拼接 |
| `Slot::child($name, $shape)` | 数组 | 作为 `$shape` 的嵌套数据作用域 |
| `Slot::each($name, $shape)` | 数组的可迭代集合 | 为每个项渲染 `$shape` |
| `Slot::if($name, $then, $else = null)` | 真值判断 | 当 `$data[$name]` 为真时渲染 `$then`，否则渲染 `$else`；缺失的键为 false，且绝不抛出异常 |

修饰符：

- `->required(false)`——槽位可以缺失；键缺失与显式 `null` 都渲染为空（属性位则省略）。
- `->default($value)`——键缺失时使用的回退值，同时使槽位可选。
- 必填的值槽位与 raw 槽位既不接受缺失的键，也不接受显式的 `null`。
- `Slot::if()` 会以 `LogicException` 拒绝这两个修饰符。
- `Slot::child()` / `Slot::each()` 的嵌套作用域直接读取 `$data[$name]`，数据形状由调用方在渲染前准备好。

值转换：值槽位与 raw 槽位接受标量与 `Stringable`，因此子组件返回的字符串不需要 `(string)` 强制转换；可选槽位还接受 `null`；数组和其他对象会抛出 `InvalidArgumentException`，并在信息中给出完整槽位路径。只有 raw 槽位额外接受可字符串化值的可迭代集合，并把它拼接起来——已经渲染好的行列表可以原样传入，不需要 `implode()`。嵌套数组仍然是一个错误。

## 作用域与缺失数据

`Slot::child()` 与 `Slot::each()` 会创建嵌套数据作用域；在其中，槽位针对该作用域解析。缺失必填键会抛出带完整路径的 `Pure\Core\MissingSlotException`，例如 `slot 'items[].title' is required but was not provided.`；错误信息会建议最接近的已提供键名（binding 拼写错误），或列出该作用域实际提供的键；必填的值槽位与 raw 槽位显式传入 `null` 时抛出 `slot 'items[].title' is required but was null.`。可选数据请使用 `default()` 或 `required(false)`。

`Slot::if()` 的分支共享当前作用域，因此下面这样写可以自然工作：

```php
$item = Compile::shape(
    li(
        Slot::value('name'),
        Slot::if('admin', span('(admin)'))
    )
);
```

## 组件

组件是一个 `*.cmp.php` 单元：带类型化参数、返回 `string` 的函数，加上紧挨着注册的惰性工厂
（参见[组件](/zh/guide/components)与[缓存](#缓存)中的 PHP-FPM 场景）：

```php
<?php

// Card.cmp.php
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn () =>
    div(
        h2(Slot::value('title')),
        p(Slot::value('content'))
    )->class(Slot::value('class'))
);

function Card(string $title, string $content, string $class = 'card'): string
{
    return render('Card', title: $title, content: $content, class: $class);
}
```

模板内部：嵌套形状用 `Slot::child()`，列表用 `Slot::each()`，可选/条件标记用
`Slot::if()`，已渲染的子组件经 `Slot::raw()` 注入。

### 列表

```php
$row = Compile::shape(li(Slot::value('label')));

$shape = Compile::shape(ul(Slot::each('rows', $row)));
$shape(['rows' => [['label' => 'a'], ['label' => 'b']]]);
```

### 混合列表

一个形状只有一种结构，因此列表项需要不同标记时，在数据层分派：逐项调用合适的组件函数，
把拼好的标记交给 raw 槽位。

```php
function Blocks(array $blocks): string
{
    $html = '';

    foreach ($blocks as $block) {
        $html .= $block['kind'] === 'link'
            ? LinkBlock($block['value'], $block['href'])
            : TextBlock($block['value']);
    }

    return $html;
}

$shape = Compile::shape(div(Slot::raw('blocks')));
$shape(['blocks' => Blocks($blocks)]);
```

同构列表用 `Slot::each()`；变体只是单个 item 内部的细节时，可以用预置的布尔键配合
`Slot::if()` 把分派留在模板里。

开启 opcache 后，一页里每个组件产物的 require 约 0.5µs（22 个产物约 10µs，见
`bench/registry.php`），因此「产物 + opcache」就是生产路径。单文件 bundle 曾按该数据做过原型
并被否决：冷启动比全部可读模板加起来更慢，热路径打平，因此库不再提供 bundle。

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

## 预编译产物

磁盘缓存仍会在每个请求中重建形状树。若要在部署时完全不构建形状，可以用 `pure` 命令提前编译：

```bash
vendor/bin/pure compile src/shapes
```

每个返回 `Shape` 的 `*.shape.php` 文件会被编译成相邻的 `*.pure.php` 产物。产物带有形状指纹并返回一个 `Renderer`，因此既不需要形状树，也不需要编译缓存：

```php
$page = require __DIR__ . '/page.pure.php';

echo $page->render(['title' => 'Users']);
$page->save(__DIR__ . '/out.html', ['title' => 'Users']);
```

- `pure compile <路径>...` 接受文件与目录（递归查找），同时发现 `*.shape.php` 模板与
  `*.cmp.php` 单元，并跳过内容已最新的文件：shape 仍会被加载与编译（因此它引用的任何文件
  发生变化都能被感知），但内容一致的文件会以 `unchanged:` 报告而不是重写。`--list` 不编译，
  直接按 `name -> file (component|page)` 打印发现的单元。`pure compile --check` 不写入任何
  文件，当产物过期或缺失时以退出码 1 结束，适合放在 CI 步骤中。`--plain` 会额外写出下文的
  「无依赖视图」，`--check --plain` 同时校验两种形态。仓库中的示例都是 `*.cmp.php` 单元，
  `vendor/bin/pure compile examples` 可一次编译全部。
- 产物的渲染结果与运行时编译器完全一致（测试按逐字节比对断言），并且读起来就像模板：
  标记仍是标记，动态值写成 `<?= ... ?>`，控制流使用替代语法，闭包只定义一次并导入类的短名。
  HTML 片段承载的是精确的渲染字节，因此不会被重新缩进。产物的 `Renderer::$source`
  为空——文件本身就是源码。
- 动态值通过 `TemplateRuntime` 读取槽位，编译语义集中在一处：必填槽缺失时抛出
  `MissingSlotException`（必填的值槽位与 raw 槽位显式传入 `null` 也会失败，属性槽位则保持
  按 `null` 省略），`default:` 提供可选槽的编译期默认值，转义与强制转换与平铺渲染器完全一致；
  仅当槽位路径与键名不同时才出现 `path:`。
- 产物还携带根作用域槽位清单（`Renderer::$slots`），因此开发守卫无需重建形状树就能报告
  模板从未读取的 binding。

```php
$pureBody = static function (array $v): string {
    ob_start();
    try { ?><div class="card"><h1><?= TemplateRuntime::text($v, 'title') ?></h1><ul><?php
        foreach (TemplateRuntime::items($v, 'items') as $item1):
            $v2 = TemplateRuntime::scope($item1, 'items[]'); ?><li><?= TemplateRuntime::text($v2, 'label', path: 'items[].label') ?></li><?php
        endforeach; ?></ul></div><?php
    } finally {
        $out = (string)ob_get_clean();
    }

    return $out;
};
```

没有自动的文档声明：树按原样渲染，整份文档的文档声明（HTML 根是
`<!DOCTYPE html>`，XML/SVG 根是 XML 声明）由调用方通过根标签的 `documentHeader()` 自己拼接。
`examples/bootstrap` 的组件都是建立在其上的单元：

```php
// components/Icon.cmp.php：类型化 props，背后是预编译模板
register('Icon', __FILE__, static fn () =>
    svg(svgUse()->href(Slot::value('href')))->class(Slot::value('class'))
);

function Icon(string $href, string $class = 'bi'): string
{
    return render('Icon', href: $href, class: $class);
}

// views/features.cmp.php：页面骨架加已渲染的正文
register('Features', __FILE__, static fn () => html(/* ... */));

function featuresPage(): string
{
    // 手动补上文档声明；树本身不带文档声明。
    return '<!DOCTYPE html>' . render('Features',
        title: FeaturesService::pageTitle(),
        content: FeaturesBody(),
    );
}
```

每个区块都从 service 层（bootstrap 示例中的 `FeaturesService`）取自己的记录，因此页面函数
不携带页面数据，给组件加一个 prop 也永远不需要改页面。

子组件渲染出的字符串直接进入 raw 槽——无需 `(string)` 强制转换——它们组成的列表按顺序拼接。

`Pure\Component\render()` 会在 shape 文件旁边存在产物、且产物不早于 shape 文件时直接加载产物，
否则调用注册的工厂（每个编译 generation 一次）或编译 shape 文件（磁盘缓存仍然生效）。它只
返回片段——要文档声明就由调用方自己拼接。

它的 `PlainFeaturesController` 把同一份 bindings 交给示例自带的 `plain()` 助手（一个应用
函数：它 require 视图文件并展开数据）；单一入口
`public/index.php` 为每个页面同时提供两种形态：`/pure/features`、`/pure/pricing` 走页面函数，
`/plain/features`、`/plain/pricing` 走普通视图，开发时可以对照。
- 请使用与生产环境相同的 PHP 次版本号构建产物：指纹与产物头部都嵌入了 PHP 版本（与缓存一致）。
- 产物是构建输出：修改形状后需要重新构建。加载时不会校验形状树，因此请用 `--check`
  发现过期产物。
- 产物还带有它的 `Compile::CACHE_VERSION`：加载由库的其他版本写出的产物时，抛出的是带
  `pure compile` 提示的异常，而不是在产物所描述的 `Renderer` 签名上报错。版本号提升本身就会
  使 `Compile::cachePath()` 里的渲染器失效，却绝不会使模板旁的产物失效，所以 `pure compile`
  是升级流程的一部分。
- 新鲜度用 `filemtime()` 比较，它的整秒粒度意味着与单元在同一秒写入的产物就已经可用。
  这是有意为之：tar、rsync 或 git checkout 造成的 `touch` 式时间偏移很常见，精确比较会丢弃
  这些产物并逐请求重新编译。而对每个单元做内容哈希实测约每个文件 7 µs，相比之下开启
  opcache 后 require 它的产物约 0.5 µs，所以它也算不上更便宜的守卫。
- 两个会写出同一个产物的源文件（`a.shape.php` 紧邻 `a.cmp.php`）会被 `pure compile` 一并
  拒绝并返回退出码 1，因此 `a.pure.php` 归属哪个模板不会由发现顺序决定。
- 加载形状文件时产生的输出会被丢弃；`pure compile` 只输出构建信息。

### 契约检查

`pure check` 静态校验每个单元的契约，让不匹配在 CI 中失败，而不是等到渲染时：

```bash
vendor/bin/pure check src
```

- 模板读取的**槽位**与组件函数 `render()` 调用的**具名绑定**：绑定了一个模板不读取的键
  是错误（并给出 `did you mean` 建议），必填槽位没有被绑定也是错误。展开的绑定数组在
  辅助函数只返回一个字面量数组时会被解析（`render('Features', ...featuresBindings())`），
  运行时计算的则报告为 `info`。
- 组件函数的**参数类型**与槽位种类：列表槽需要可迭代值、child 作用域需要数组、值槽需要
  可字符串化值、raw 槽两者皆可。必填槽对应的可空参数是警告（绑定 `null` 会抛出
  `MissingSlotException`）；既未被函数使用、也不是模板槽位的参数同样是警告。
- 没有组件函数的链式单元改查它的 **`prepare()` 闭包**：参数就是 prop 契约，必须与槽位
  的名字和类型一致；它返回的字面量数组的键必须是模板读取的槽位（运行时计算出的返回值
  报告为 `info`）。
- `prepare()` 参数上的 **`#[Prop]` 注解**用来声明签名表达不了的信息：`slot` 是该 prop
  绑定的槽位名，`item` 是列表 prop 的每一项在 `Slot::each` 的 item 形状里填的槽位，
  `required` 是调用方的义务，`deprecated` 是迁移提示。声明会与签名和模板比对；当
  `prepare()` 返回的不是一个可读的字面量数组时，模板的必填槽位改为与声明比对；调用点绑定
  了已废弃的 prop 会得到警告。
- 每个被检查文件里的**链式调用**：目标不接受的 `->prop(...)` 是错误（附 `did you mean`
  建议），`->children(...)` 会提示应把 children 传给调用本身，从变量展开的 prop 集合会
  被跳过。目标必须在被检查的文件中，才能得知它接受哪些 props。
- 同一个模板把一个槽位同时用作标量（value/raw）与作用域（child/each）是错误；
  `*.shape.php` 模板也会做这项检查。

有错误时退出码为 1，带 `--strict` 时警告也返回 1。`pure check` 不看产物——
产物新鲜度由 `pure compile --check` 负责。

### 组件产物与缓存策略

每个组件都是一个 `*.cmp.php` 单元（也支持 `*.shape.php` 模板），因此 `pure compile` 会像
其他形状一样为它构建产物。绑定器会比较产物与单元文件的 mtime：产物较新就直接加载（不调用
工厂、不建树、不算指纹），过期或缺失则调用注册的工厂或编译 shape 文件。CI 中用
`pure compile --check` 可以发现过期产物（退出码 1）。

开启哪些取决于部署形态：

- **PHP-FPM**——开启 `Compile::cachePath()` 并构建产物。没有产物时，每个请求都要为该组件
  重建形状树、遍历指纹，然后才渲染：仅 features 页面的骨架编译就要 ~780 µs（冷启动）、
  ~260 µs（磁盘缓存命中，`bench/cache.php`）。产物把这段降为一个 `require`，而在开启
  opcache 时一次 require 远低于 1 µs。
- **长驻 worker**（RoadRunner、Swoole、FrankenPHP）——开启 `Compile::cachePath()` 并保留
  绑定器的路径缓存（`render()` 内置，内联树用 `static $render`）；renderer 常驻内存，产物可选。
- **`opcache.preload`**——preload 只把代码常驻内存，不会让 static 变量跨请求保留（PHP preload
  RFC 已明确说明），因此不能替代上面两种做法。

### 无依赖导出（可选）

`pure compile --plain` 会在产物旁边额外写出 `*.plain.php`：只有标记与原生 PHP，
渲染时不需要安装 purephp。加载方式就是经典的视图约定——把数据数组展开成局部变量：

```php
ob_start();
extract($data, EXTR_SKIP);
require 'views/index.plain.php';
$html = (string)ob_get_clean();
```

需要让视图脱离库运行时才用 `--plain`：例如部署只带 `public/` 与 `views/`，
或把模板目录交给其他人。常规数据下无依赖视图逐字节等于产物，并且仅当其根是文档根
（`<html>` 或 XML 树）时前面补上文档声明：页面保留自己的 `<!DOCTYPE html>` / XML 声明，
而片段（`div`、内联 SVG 图标）视图直接以标记开头，因此被 include 时绝不会把文档声明
插进文档中间。而且它是最快的形态：值直接进入 `htmlspecialchars()`，没有运行时访问器调用。
但它是普通视图而非编译组件，严格槽位语义仍由产物提供——语义差异与
`@var` 注解说明见[无依赖视图注意事项](#无依赖视图注意事项)。

## 性能

每个请求有两项开销：进程拿到一个渲染器要付的代价，以及用它渲染要付的代价。实测行见
`bench/README.md`；绝对数值会随 PHP 版本、opcache 与 CPU 变化，所以先在自己的机器上跑一遍
再与下表对照（PHP 8.1.34，单个 604 元素、200 行的页面，`php bench/compare.php`）。

| 路径 | 每次渲染耗时 | 端到端加速 |
| --- | --- | --- |
| 构建树 + `render()` | ~1.2 ms | 1× |
| 仅渲染（复用同一棵树） | ~345 µs | 3.4× |
| 编译形状 + 数据 | ~180 µs | 6.6× |
| 编译静态树（字面量） | < 1 µs | — |

开启 opcache 后，构建树依然昂贵，而编译路径几乎不变，因此加速比落在 5.5×，开启 JIT 时为
4.6×。预编译产物路径把构建从第二列里彻底移除：对单个页面形状，构建加编译约 2.9 ms，而
require 它的产物只需 ~25–67 µs（`php bench/artifact.php --write && php bench/artifact.php`）。

一整个页面的开销取决于它如何组合。bootstrap 的 features 页面用组件函数拼正文，因此
`examples/bootstrap/bench.php` 量的是真实页面而不是单个形状：经典树 ~340 µs/op，页面函数
跑在它的产物之上 ~104 µs/op（2.8–3.3×），普通视图 ~20 µs/op。该基准的
`skeleton artifact + bindings` 行渲染的是页面*模板*，组件标记已经绑定好了，所以它的
~1.5 µs 是每个形状的数值，而不是一次页面渲染。

## 限制

- 标签名不能依赖数据：形状始终使用相同的标签。结构变化请使用 `Slot::if()`，混合列表在数据层
  分派（见[混合列表](#混合列表)），或者在渲染前规范化数据。
- 编译后的代码与形状结构绑定；改变形状会改变它的 `id()`，从而改变其缓存文件。
- 编译时会读取当前的形状树，`id()` 也反映调用时刻的树。已编译的渲染器会持续渲染它编译时的那份树，因此在修改已包装为形状的树之后需要调用 `Compile::flush()`；每个进程只构建一次形状即可完全避免此问题。
- 形状不得包含请求数据——它们是进程级产物。

## 经典组件 → PurePHP 映射

| 经典组件 | PurePHP 组件 |
| --- | --- |
| `function Card(array $props): HTML` | `function Card(string $title): string` 加一个 `Card.cmp.php` 单元（函数 + 模板） |
| `h2($title)` | `h2(Slot::value('title'))` |
| `->class($classList)` | 静态值直接 `->class($classList)`，动态值用 `->class(Slot::value('classList'))` |
| `array_map(fn ($row) => Row($row), $rows)` | 在组件函数里循环，经 `Slot::raw()` 注入 |
| `if ($show) { ... }` | `Slot::if('show', Shape)` |
| `<Child($props)>` | 调用 `Child(...)` 并把它返回的 `string` 经 `Slot::raw()` 注入 |

即时（`render()`）标签树仍然可用于代码片段与调试；参见[基本用法](/zh/guide/basic-usage)。

## 缓存与运维细节

- `Compile::cachePath($dir)` 开启磁盘渲染器缓存；传 `null` 关闭（默认）。目录必须是
  私有目录：归 PHP 运行用户所有、组与其他用户不可写（缺失时以 0700 创建）、并位于
  Web 根目录之外——`cachePath()` 会拒绝权限过松或属主不符的目录；不要把缓存目录
  直接指向 `/tmp` 这类共享位置。
- 缓存文件以 `Shape::id()` 为内容寻址，形状变化会生成新文件；写入是原子的（临时文件 +
  重命名），并发 worker 安全；缓存文件是普通 PHP，对 opcache 友好。
- `Compile::clearCache()` 删除由本库写入的文件。
- `Compile::flush()` 使内存中的渲染器失效（部署后的长驻 worker 中很有用）。
- 要发现每个请求都重新构建（而不是被记忆化）的形状，请启用开发守卫：
  `Compile::guard(true)` 或设置 `PURE_COMPILE_GUARD=1`。当同一个调用点在一个进程中
  调用 `Compile::shape()` 次数过多时，PHP 会发出 `E_USER_WARNING`，建议采用
  `static $shape ??=` 模式。同一个开关也会打开渲染期检查：模板从未读取的数据键会被报告
  （并给出 `did you mean` 建议），与标准属性名只差一个字符的属性方法会发出警告，而不是
  静默变成自定义属性；每条警告在单个进程内每个对象只触发一次。
- `Compile::CACHE_VERSION` 在生成代码格式或指纹构成变化时递增。由其他版本写出的
  产物加载时抛出带 `pure compile` 提示的 `RuntimeException`；`*.plain.php` 视图只在
  注释中携带版本号、没有可执行守卫，升级后会静默输出过期内容，直到
  `pure compile --check --plain` 发现不一致。

## 无依赖视图注意事项

无依赖视图是标记 + 原生 PHP——脱离 purephp 也能渲染，但它不携带编译渲染器的
严格槽位语义：

- 必填槽缺失是未定义变量，不再抛出 `MissingSlotException`；
- 必填槽显式传入 `null` 时渲染为空，而不是像产物那样（值槽与 raw 槽）失败；
- 文档根的视图会带上 `<!DOCTYPE html>` / XML 声明，而片段视图以标记开头；
- `null` 属性输出为空值，而不是整个属性消失；
- 列表槽不再校验可迭代性，值的字符串化交给 PHP 而不是 `SlotRuntime`；
- raw 槽只输出单个值：可字符串化值的可迭代集合不会被拼接。请由控制器传入
  `implode('', $rows)`，或者绑定字符串。

顶层槽读取为普通变量，嵌套槽读取为它所在的数组，转义直接内联，因此它和手写
模板一样可移植。

使用函数组件时，控制器会先渲染组件、再把它们的标记作为 raw bindings 传给页面
形状，因此视图文件仍然无依赖，而请求处理器会用到库。

视图会按形状结构为每个顶层槽生成 `@var` 注解，静态分析器无需排除规则或额外
配置即可读取这些展开的局部变量：值槽是 `scalar|null|\Stringable`（即
`htmlspecialchars()` 可接受的类型），条件槽是 `mixed`，child 与列表作用域会推导成
array shape 及它们的 iterable；特殊槽名声明在加载器的 `$data` 数组上。这些注解
只是注释，不增加任何输出字节。唯一仍会告警的是没有数组默认值的可选容器槽：
生成的读取会回退到 `null`，注解如实反映这一点。

视图是 include，请在生产开启 opcache：关闭时每次渲染都会重新解析文件，那是
无依赖视图唯一比产物更快的场景。
