# 组件

组件就是一个带类型化参数、返回 `Raw` 标记的 PHP 函数。它背后的模板是一个 shape 文件，由
`pure compile` 预编译；`render()` 把两者在一个表达式里绑定起来。

## 第一个组件

```php
<?php

// components/Card.shape.php——模板：静态标记加槽位
return Compile::shape(
    div(
        h2(Slot::text('title')),
        p(Slot::text('content'))
    )->class('card')
);
```

```php
<?php

// components/Card.php——组件：类型化 props 进，Raw 标记出
use Pure\Core\Raw;

use function Pure\Component\render;

function Card(string $title, string $content): Raw
{
    return render(
        __DIR__ . '/Card.shape.php',
        title: $title,
        content: $content
    );
}

echo Card('Title', 'Content');
```

`render()` 把 shape 文件绑定成 `数据 → Raw` 函数，并按路径缓存这个绑定器，所以模板每进程只
加载一次：相邻的 `Card.pure.php` 产物较新时直接用它，否则编译 `Card.shape.php`。槽位值按
名字传入，也可以传解包的字符串键数组（`render($file, ...$bindings)`）。用
`vendor/bin/pure compile components` 构建产物。

## Props

props 就是函数参数：给它们类型和默认值，然后传进模板的槽位。永不变化的值可以直接写死在
模板里，每次渲染都可能变化的值放进 bindings。

```php
<?php

function Badge(string $label, string $class = 'badge'): Raw
{
    return render(__DIR__ . '/Badge.shape.php', label: $label, class: $class);
}
```

```php
<?php

// components/Badge.shape.php
return Compile::shape(
    span(Slot::text('label'))->class(Slot::attr('class'))
);
```

## 组合组件

父组件调用子组件，并通过 `Slot::raw` 注入它们的输出：

```php
<?php

// components/Button.shape.php
return Compile::shape(
    button(Slot::raw('icon'), Slot::text('label'))->class('btn')
);

// components/Button.php
function Button(Raw $icon, string $label): Raw
{
    return render(__DIR__ . '/Button.shape.php', icon: $icon, label: $label);
}

Button(Icon('#plus'), 'Add');
```

列表同理：在组件函数里循环、拼接标记，再把字符串传给 raw 槽。若列表项只是普通数据行、
不需要逐项组件逻辑，可以在模板里直接用 `Slot::each()`。

## 绑定器 API

`render()` 是下面两个底层助手的便捷形式：

- `component($source)` 返回模板的 `数据 → Raw` 绑定器。
- `page($source)` 同上，并补上根标签的文档声明（`html()` 根的 `<!DOCTYPE html>`）。

模板是内联树（`component(div(Slot::text('title')))`）时，或者你想自己持有绑定器变量时，
就用它们：

```php
<?php

function Tag(string $label): Raw
{
    static $render;
    $render ??= component(div(Slot::text('label'))->class('tag'));

    return $render(['label' => $label]);
}
```

`render($file, ...)` 与 `renderPage($file, ...)` 是面向 shape 文件的单表达式写法；两者都按
路径缓存绑定器，所以文件形式的组件永远不需要自己写 `static` 变量。

## 页面

`renderPage()` 等于 `render()` 加上文档声明：

```php
<?php

function featuresPage(array $data): Raw
{
    return renderPage(__DIR__ . '/features.shape.php', [
        'title' => $data['title'],
        'content' => (string) FeaturesBody($data['content']),
    ]);
}
```

页面模板也是普通的 shape 文件，因此页面同样拥有产物；控制器只需打印结果。普通视图形态
（`pure compile --plain`）会从无依赖的视图文件打印同一份 bindings。

## 缓存

- `render()` / `renderPage()`——绑定器按模板路径缓存，产物或 shape 每进程只加载一次。
- `Compile::cachePath($dir)`——请求加载已生成的 renderer，而不是重新生成。
- `pure compile` 产物——产物较新时绑定器完全跳过形状树与指纹；`pure compile --check`
  让 CI 把过期产物拦下来。
- 长驻 worker 会把记忆化的 renderer 留在内存里，产物在那里是可选项。

完整的缓存策略见[编译产物](/guide/compiled)。

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
[Props 与槽位](/guide/props)。

## 下一步

- [编译产物](/guide/compiled)——产物、缓存与无依赖视图
- [Props 与槽位](/guide/props)——完整的数据绑定参考
- [事件](/guide/events)——事件属性与浏览器端处理器
