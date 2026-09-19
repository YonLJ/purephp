# 什么是 PurePHP?

PurePHP 是一个受 ReactJS 函数式组件启发的 PHP 模板引擎。你用看起来像 HTML 的 PHP 对象树来描述 UI，PurePHP 将它转换为 HTML 字符串——一切都是 100% 原生 PHP，无需学习任何模板语法。

PurePHP 有两条渲染路径：

| 路径 | 你写什么 | 何时使用 |
| --- | --- | --- |
| **编译渲染** | 带 `Slot` 占位符的无数据*形状*树，每个 worker 进程编译一次（或从渲染器缓存加载），每个请求用普通数据渲染 | 生产环境中的页面与组件 |
| **即时渲染** | 包含真实值的标签树，用 `render()` / `print()` 当场渲染 | 代码片段、原型、CLI 工具与调试 |

## 为什么选择 PurePHP？

在传统的 PHP 开发中，视图层往往需要混合 HTML、PHP 代码和其他模板语法，这会让开发者感到困惑。PurePHP 通过以下方式解决这些问题：

- **纯 PHP 实现**：所有代码都是 100% 原生 PHP，无需学习新的模板语法
- **组件化开发**：用可复用的组件形状代替重复的 HTML
- **类 HTML 语法**：标签辅助函数与方法链式调用看起来非常接近 HTML
- **编译渲染**：静态标记在编译期只转义一次，因此渲染开销仅比字符串拼接略高

## 编译渲染

页面只描述一次，在渲染时绑定数据：

```php
<?php

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\{div, h1, p};

function pageView(array $data): string
{
    static $render;
    $render ??= Compile::shape(
        div(
            h1(Slot::value('heading')),
            p(Slot::value('lead'))
        )->class('container')
    );

    // 引擎不附加文档声明，在这里手动拼接。
    return '<!DOCTYPE html>' . $render([
        'heading' => $data['heading'],
        'lead' => $data['lead'],
    ]);
}

echo pageView(['heading' => 'Welcome to PurePHP', 'lead' => 'A PHP template engine']);
```

renderer 被记忆化到 `static` 变量中，每个进程只编译一次——这适用于长驻 worker。标准
PHP-FPM 下每个请求都是全新的，请启用 `Compile::cachePath()`，或用
`vendor/bin/pure compile` 预编译模板，让请求加载产物而不是重建。
组件、列表、条件与缓存请参见[组件](/zh/guide/components)与
[编译组件](/zh/guide/compiled)。

## 即时渲染

对于代码片段与调试，可以用真实值构建标签树并直接打印：

```php
<?php

use function Pure\HTML\{div, h1, p};

div(
    h1('Welcome to PurePHP'),
    p('A PHP template engine')
)->class('container')->print();
```

## 优势

1. **简单易用**：API 设计简单直观，学习曲线平缓
2. **快速**：编译后的形状渲染速度与编译型模板引擎持平
3. **类型安全**：完整支持 PHP 的类型系统，提供更好的开发体验
4. **轻量级**：核心库体积小巧，没有多余的依赖

## 下一步

- [快速开始](/zh/guide/getting-started) - 学习如何创建你的第一个 PurePHP 应用
- [编译组件](/zh/guide/compiled) - 以生产环境的方式构建页面与组件
- [基本概念](/zh/guide/concepts) - 理解 PurePHP 的基础知识
- [基本用法](/zh/guide/basic-usage) - 学习代码片段所用的标签 API
