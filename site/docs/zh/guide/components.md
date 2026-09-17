# 组件

组件是 PurePHP UI 的构建单元。组件是返回 `Shape` 的 PHP 函数；它每个进程只编译一次（长驻 worker；标准 PHP-FPM 下请启用 `Compile::cachePath()`，让请求加载已编译的渲染器），然后可以用不同数据按需渲染任意多次。

## 函数组件

组件把静态配置作为函数参数，并用槽位描述动态值。将形状记忆化到 `static` 变量中，使编译步骤每个进程只发生一次：

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, p};

function CardShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h2(Slot::text('title')),
            p(Slot::text('content'))
        )->class('card')
    );
}

// 使用数据渲染组件
CardShape()->print([
    'title' => 'Title',
    'content' => 'Content',
]);
```

绝不要在请求处理器中调用 `Compile::shape()`；当一个调用点反复构建形状时，守卫（`Compile::guard(true)`）会发出警告。

## 组件 props

### 1. 静态 props

静态 props 会成为函数参数。按参数值分别记忆化，让每个变体都有自己的形状：

```php
<?php

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

CardShape('card shadow')->print([
    'title' => 'Shadowed',
    'content' => 'Static props are function arguments',
]);
```

### 2. 动态 props

动态 props 是槽位，在渲染时绑定：

```php
<?php

$shape = Compile::shape(
    button(Slot::text('label'))->type('button')->class(Slot::attr('class'))
);

$shape(['label' => 'Save', 'class' => 'btn btn-primary']);
```

### 3. 事件 props

事件处理器是标签上的静态属性（`->onclick(...)`、`->onchange(...)`）；浏览器端处理器由其名称标识，因此它是形状的一部分：

```php
<?php

$shape = Compile::shape(
    button(Slot::text('label'))->onclick('handleClick()')
);

$shape(['label' => 'Click me']);
```

## 子组件

`Slot::child()` 会嵌入另一个形状，并为其创建嵌套数据作用域：

```php
<?php

function IconShape(string $class = 'icon'): Shape
{
    static $shapes = [];

    return $shapes[$class] ??= Compile::shape(
        span(Slot::attr('glyph'))->class($class)
    );
}

function ButtonShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        button(
            Slot::child('icon', IconShape()),
            Slot::text('label')
        )->class('btn')
    );
}

ButtonShape()->print([
    'icon' => ['glyph' => '+'],
    'label' => 'Add',
]);
```

当子组件需要与父组件不同的数据形状时，在数据层派生子作用域：child 槽读取 `$data[$name]`，因此绑定数据里带上子组件期望的嵌套数组即可。

```php
<?php

Slot::child('user', BadgeShape()); // 读取 $data['user']

$shape(['user' => ['label' => 'ADA']]);
```

## 列表

`Slot::each()` 为每个项渲染一次子形状：

```php
<?php

$row = Compile::shape(li(Slot::text('label')));
$list = Compile::shape(ul(Slot::each('rows', $row))->class('list'));

$list(['rows' => [['label' => 'a'], ['label' => 'b']]]);
```

每个项都会成为子形状的数据作用域；缺失键遵循通常的规则（`default()`、`required(false)` 或 `MissingSlotException`）。

## 条件渲染

`Slot::if()` 根据数据键的真值渲染分支。缺失的键就是 false——绝不会抛出异常——并且各分支共享当前作用域：

```php
<?php

$shape = Compile::shape(
    div(
        Slot::if('admin', Compile::shape(span('Administrator')), Compile::shape(span('Guest')))
    )
);

$shape(['admin' => true]);  // <div><span>Administrator</span></div>
$shape([]);                 // <div><span>Guest</span></div>
```

## 混合列表

`Slot::eachKind()` 按判别键逐项分派（默认为 `kind`）：

```php
<?php

$shape = Compile::shape(div(Slot::eachKind('blocks', [
    'text' => Compile::shape(p(Slot::text('value'))),
    'link' => Compile::shape(a(Slot::text('value'))->href(Slot::attr('href'))),
])));

$shape(['blocks' => [
    ['kind' => 'text', 'value' => 'hello'],
    ['kind' => 'link', 'value' => 'docs', 'href' => '/docs'],
]]);
```

缺少判别键或判别键取值未知的项会抛出 `InvalidArgumentException`，并在信息中给出完整路径（`blocks[].kind`）。

## 组件组合

组件通过嵌套形状来组合——既可以直接嵌套在父形状中，也可以通过 `Slot::child()`：

```php
<?php

function PageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        main(
            Slot::child('header', HeaderShape()),
            Slot::each('cards', CardShape())
        )->class('page')
    );
}
```

由于形状不含数据，一个组件形状可以在多个页面中复用而不产生额外开销：它只编译一次，然后内联到每个父级编译器中。

## 即时渲染（代码片段）

对于一次性片段，你可以完全跳过形状，直接渲染标签树：

```php
<?php

use function Pure\HTML\{div, h2, p};

div(h2('Title'), p('Content'))->class('card')->print();
```

仅将这种方式用于代码片段与调试；生产页面应当编译形状，让转义与结构成本只支付一次。

## 下一步

- [编译组件](/zh/guide/compiled) - 缓存、守卫与限制
- [Props 与槽位](/zh/guide/props) - 完整的数据绑定参考
- [事件](/zh/guide/events) - 事件属性与浏览器端处理器
