# 组件

一个组件就是一个文件：带类型化参数、返回 `string` 标记的 PHP 函数，紧挨着它渲染的模板。文件
里注册一个惰性工厂，因此 `pure compile` 可以预编译模板，而请求只加载产物。

## 第一个组件

```php
<?php

// components/Card.cmp.php——组件单元：函数 + 模板
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2, p};

register('Card', __FILE__, static fn () =>
    div(
        h2(Slot::value('title')),
        p(Slot::value('content'))
    )->class('card')
);

function Card(string $title, string $content): string
{
    return render('Card', title: $title, content: $content);
}

echo Card('Title', 'Content');
```

- `register()` 只保存工厂与文件，不构建任何东西；产物较新的请求永远不会调用工厂。
- `render('Card', ...)` 渲染注册的模板，槽位值按名字传入（也可以传解包的字符串键数组：
  `render('Card', ...$bindings)`）。
- 用 `vendor/bin/pure compile components` 在单元旁生成 `Card.pure.php`（加 `--plain` 还会
  生成 `Card.plain.php`）。`pure compile --list` 会打印发现的所有单元。

注册名与单元文件路径可以互换：`render(__DIR__ . '/Card.cmp.php', ...)` 解析到同一个绑定器，
所以组件既能按名渲染，也能按文件渲染。

## Props

props 就是函数参数：给它们类型和默认值，然后传进模板的槽位。永不变化的值可以直接写死在
模板里，每次渲染都可能变化的值放进 bindings。

```php
<?php

// components/Badge.cmp.php
register('Badge', __FILE__, static fn () =>
    span(Slot::value('label'))->class(Slot::value('class'))
);

function Badge(string $label, string $class = 'badge'): string
{
    return render('Badge', label: $label, class: $class);
}
```

[链式调用](#链式调用) 则把同样的 props 写成 setter
（`Badge('Save')->label('Save')->class('badge')`），类型契约交给 `prepare()` 闭包。

## 链式调用

组件调用可以写得和标签一样：props 用同样的链式 setter 设置，children 直接传给调用，
返回值可以像标签一样嵌套。

```php
<?php

// components/Card.cmp.php —— 同一个单元，改用链式调用
use Pure\Compile\Compile;
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{button, div, h2, li, ul};

register('Card', __FILE__, static fn () => div(
    Slot::raw('children'),
    h2(Slot::value('type'))->class('card-title'),
    ul(Slot::each('features', li(Slot::value('value')))),
    button(Slot::value('text'))->class(Slot::value('class'))
)->class('card'));

function Card(mixed ...$children): Call
{
    return component('Card', ...$children);
}

echo div(
    Card(h2('Pro'))
        ->type('Free')
        ->features([['value' => '10 users'], ['value' => '2 GB']])
        ->text('Sign up for free')
        ->class('btn btn-lg btn-block btn-outline-primary')
);
```

- `component($name, ...$children)` 返回 `Pure\Component\Call`，它实现了
  `Pure\Core\Markup`：`div(Card(...))` 会原样输出并随父树延迟渲染，和标签子节点一致。
- props 绑定槽位名，模板用 `Slot::value()`、`Slot::each()`、`Slot::child()` 读取。
  `class()` 与 `style()` 的合并规则与标签 setter 完全相同；`null` 表示不设置该 prop
  （槽位随后按“未提供”处理，或回退到默认值）。
- children 绑定保留槽位 `children`，模板用 `Slot::raw('children')` 读取。不传 children
  时渲染为空；模板没有 `children` 槽位却传了 children 会抛出异常。
- 模板不读取的 prop 会由开发守卫给出 `did you mean` 提示，`pure check` 也能静态发现。
- `render('Card', ...)` 仍是低层入口；两种写法共用同一个绑定器、产物、缓存与错误。

### 用 prepare() 给 props 加类型

链式调用把 props 当作数据传递，因此类型放在 `prepare` 闭包里而不是调用函数里。它的参数
就是 prop 契约——PHP 会强制类型，缺失或未知的 prop 在渲染前就报错——返回的数组就是绑定
模板的数据：

```php
<?php

register('Section', __FILE__,
    factory: static fn () => Compile::shape(...),
    prepare: static function (string $section, string $class, callable $item): array {
        $data = FeaturesService::section($section);

        return [
            'title' => $data['title'],
            'contents' => array_map(static fn (array $record): string => $item(...$record), $data['items']),
            'class' => $class,
        ];
    }
);

Section()->section('columns')->class('row g-4')->item(IconColumn(...));
```

没有 `prepare` 闭包时，props 直接就是 bindings，适合纯模板组件。`pure check` 会把
`prepare()` 的参数与返回的键同模板槽位逐一比对。

### 用 #[Prop] 声明 props 契约

签名表达不了全部信息：prop 与槽位名字不一致时它绑定谁、列表 prop 的每一项长什么样、
某个 prop 是否准备废弃。`#[Prop]` 注解把这些事实写出来，让 `pure check` 去校验，而不是
靠推断：

```php
<?php

use Pure\Component\Prop;

register('Card', __FILE__,
    factory: static fn () => Compile::shape(...),
    prepare: static function (
        #[Prop(slot: 'title')] string $text,
        #[Prop(item: 'value')] array $features,
        #[Prop(required: false)] ?string $class = null,
        #[Prop(deprecated: 'use class()')] ?string $style = null,
    ): array {
        return ['title' => $text, 'features' => ..., 'class' => $class, 'style' => $style];
    }
);
```

- `slot` 指定该 prop 绑定的槽位名，默认与参数名相同。当 `prepare()` 返回的不是一个可读的
  字面量数组（分步构建或合并而来）时，模板的必填槽位改为与声明的槽位比对，而不再是一句
  “未做比对”的 `info`。
- `item` 指定列表 prop 的每一项在 `Slot::each` 的 item 形状里填哪个槽位，检查器会把两者
  对比。
- `required` 声明调用方的义务；与签名矛盾的声明会被报告。
- `deprecated` 携带迁移提示，`pure check` 会在每个绑定该 prop 的调用点打印。

注解只被 `pure check` 读取，渲染时完全不会查询；没有注解的单元行为与之前完全一致。

每次链式调用比 `render()` 多花约 2 微秒：调用对象、prop setter 与 `prepare()` 调用各占
一部分。产物与无依赖视图路径不受影响，`examples/bootstrap/bench.php` 会分别报告两条路径。

## 组合组件

需要包裹 markup 的组件从 raw 的 `children` 槽读取它，调用方则像标签一样把 children
传给调用：

```php
<?php

// components/Button.cmp.php
register('Button', __FILE__, static fn () =>
    button(Slot::raw('icon'), Slot::value('label'), Slot::raw('children'))->class('btn')
);

function Button(mixed ...$children): Call
{
    return component('Button', ...$children);
}

Button(Icon()->href('#plus'))->label('Add');
```

列表同理：在组件的 `prepare()` 或调用点构造子调用（或已渲染字符串）的列表并传给 raw
槽——它逐元素转成字符串后拼接，所以用不着 `implode()`。若列表项只是普通数据行、不需要逐项
组件逻辑，可以在
模板里直接用 `Slot::each()`。

## 页面

页面就是根标签为文档根（`html`、`svg`、`xml`…）的组件单元。没有单独的页面 API：
用 `register()` 注册、用 `render()` 渲染，然后自己补上根标签的文档声明（HTML 根是
`<!DOCTYPE html>`，XML/SVG 根是 XML 声明）：

```php
<?php

// views/features.cmp.php
register('Features', __FILE__, static fn () =>
    html(
        head(title(Slot::value('title'))),
        body(Slot::raw('content'))
    )
);

function featuresPage(): string
{
    // 引擎按原样输出树，文档声明在这里手动拼接。
    // 页面决定有哪些区块，每个区块自己取数据。
    return '<!DOCTYPE html>' . render('Features',
        title: FeaturesService::pageTitle(),
        content: FeaturesBody(),
    );
}
```

`render()` 按原样输出（不带文档声明），所以完整页面 = 根标签的文档声明 + 渲染出的片段。

`pure compile --plain` 会把同一个页面写成无依赖视图文件，因此没有安装 purephp 的部署也能
渲染；两种形态下控制器传入同一份 bindings。

## 绑定器 API

`render()` 是底层助手的便捷形式：

- `register($name, $file, $factory)` 把单元注册到一个名字下。
- `Registry::component($nameOrPath)` 返回单元或 shape 文件的
  `Closure(array $data): string` 绑定器，便于自己持有或传递。

内联树则编译一次并保存 shape：

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;

function Tag(string $label): string
{
    static $render;
    $render ??= Compile::shape(div(Slot::value('label'))->class('tag'));

    return $render(['label' => $label]);
}
```

`render()` 按名字或路径缓存绑定器，因此注册过的单元永远不需要自己写
`static` 变量。

## 缓存

- 单元文件旁存在不早于它的 `*.pure.php` 产物时，直接由产物提供服务；工厂与形状树完全不
  会被触碰。
- `render()` 在同一个编译 generation 内按名字或路径缓存绑定器。
- `Compile::cachePath($dir)`——请求加载已生成的 renderer，而不是重新生成。
- `pure compile --check` 让 CI 把过期产物拦下来；长驻 worker 会把已加载的 renderer 留在
  内存里，产物在那里是可选项。

开启 opcache 后，加载整页组件产物约每个 0.5µs（见 `bench/README.md`），因此「产物 +
opcache」就是生产路径。

## 即时渲染（片段）

一次性片段可以完全跳过 shape，直接渲染标签树：

```php
<?php

div(h2('Title'), p('Content'))->class('card')->print();
```

只建议用于片段与调试；生产组件应当编译模板，让转义与结构成本只付一次。

## 槽位参考

组件是函数；槽位是模板*内部*的词汇：`Slot::value()`、`Slot::raw()`、`Slot::child()`、
`Slot::each()` 与 `Slot::if()`。完整的数据绑定参考见
[Props 与槽位](/zh/guide/props)。

## 下一步

- [编译产物](/zh/guide/compiled)——产物、缓存与无依赖视图
- [Props 与槽位](/zh/guide/props)——完整的数据绑定参考
- [事件](/zh/guide/events)——事件属性与浏览器端处理器
