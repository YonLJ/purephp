# 组件

一个组件就是一个文件：带类型化参数、返回 `Raw` 标记的 PHP 函数，紧挨着它渲染的模板。文件
里注册一个惰性工厂，因此 `pure compile` 可以预编译模板，而请求只加载产物。

## 第一个组件

```php
<?php

// components/Card.cmp.php——组件单元：函数 + 模板
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
    )->class('card')
));

function Card(string $title, string $content): Raw
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
register('Badge', __FILE__, static fn (): Shape => Compile::shape(
    span(Slot::text('label'))->class(Slot::attr('class'))
));

function Badge(string $label, string $class = 'badge'): Raw
{
    return render('Badge', label: $label, class: $class);
}
```

## 组合组件

父组件调用子组件，并通过 `Slot::raw` 注入它们的输出：

```php
<?php

// components/Button.cmp.php
register('Button', __FILE__, static fn (): Shape => Compile::shape(
    button(Slot::raw('icon'), Slot::text('label'))->class('btn')
));

function Button(Raw $icon, string $label): Raw
{
    return render('Button', icon: $icon, label: $label);
}

Button(Icon('#plus'), 'Add');
```

列表同理：在组件函数里循环、拼接标记，再把字符串传给 raw 槽。若列表项只是普通数据行、
不需要逐项组件逻辑，可以在模板里直接用 `Slot::each()`。

## 页面

页面同样是单元：用 `registerPage()` 注册，绑定器会补上根标签的文档声明（`html()` 根的
`<!DOCTYPE html>`）：

```php
<?php

// views/features.cmp.php
registerPage('Features', __FILE__, static fn (): Shape => Compile::shape(
    html(
        head(title(Slot::text('title'))),
        body(Slot::raw('content'))
    )
));

function featuresPage(array $data): Raw
{
    return renderPage('Features', [
        'title' => $data['title'],
        'content' => (string) FeaturesBody($data['content']),
    ]);
}
```

`pure compile --plain` 会把同一个页面写成无依赖视图文件，因此没有安装 purephp 的部署也能
渲染；两种形态下控制器传入同一份 bindings。

## 绑定器 API

`render()` 是几个底层助手的便捷形式：

- `component($source)` 返回单元或模板的 `数据 → Raw` 绑定器。
- `page($source)` 同上，并补上文档声明。
- `register()` / `registerPage()` 把单元注册到一个名字下。

模板是内联树（`component(div(Slot::text('title')))`）时，或者你想自己持有绑定器变量时，
用 `component()`：

```php
<?php

function Tag(string $label): Raw
{
    static $render;
    $render ??= component(div(Slot::text('label'))->class('tag'));

    return $render(['label' => $label]);
}
```

`render()` / `renderPage()` 按名字或路径缓存绑定器，因此注册过的单元永远不需要自己写
`static` 变量。

## 缓存

- 单元文件旁存在不早于它的 `*.pure.php` 产物时，直接由产物提供服务；工厂与形状树完全不
  会被触碰。
- `render()` / `renderPage()` 在同一个编译 generation 内按名字或路径缓存绑定器。
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

组件是函数；槽位是模板*内部*的词汇：`Slot::text()`、`Slot::attr()`、`Slot::raw()`、
`Slot::each()`、`Slot::if()`、`Slot::eachKind()` 与 `Slot::child()`。完整的数据绑定参考见
[Props 与槽位](/zh/guide/props)。

## 下一步

- [编译产物](/zh/guide/compiled)——产物、缓存与无依赖视图
- [Props 与槽位](/zh/guide/props)——完整的数据绑定参考
- [事件](/zh/guide/events)——事件属性与浏览器端处理器
