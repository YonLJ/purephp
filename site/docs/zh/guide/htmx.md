# PurePHP 与 HTMX 集成

PurePHP 和 HTMX 构成强大的组合，让你能够构建动态、响应式的用户界面，同时保持 PHP 后端简洁。

*HTMX 片段天然适合编译形状：`hx-*` 属性是普通的静态标签属性，因此在形状上只设置一次，端点用请求数据渲染同一个形状。参见[编译组件](/zh/guide/compiled)。*

## 为什么选择这个组合？

- **PurePHP**：提供组件化的 PHP 模板渲染
- **HTMX**：提供无 JavaScript 的动态交互能力
- **完美匹配**：PurePHP 处理服务端渲染，HTMX 处理客户端交互

## 快速开始

### 1. 安装依赖

```bash
composer require yonlj/purephp
```

然后在 HTML 中引入 HTMX：

```html
<script src="https://unpkg.com/htmx.org@2.0.4"></script>
```

### 2. 创建动态组件

计数器文本是绑定的槽位；端点用新的计数渲染同一个 `CountShape()` 片段：

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{div, button, p};

function CountShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        p('Current count: ', Slot::text('count'))->id('counter')
    );
}

function CounterShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            Slot::child('counter', CountShape()),
            button('Increment')
                ->hxPost('/increment')
                ->hxTarget('#counter')
                ->hxSwap('innerHTML')
        )->class('counter')
    );
}

// 渲染页面
$bindings = ['counter' => ['count' => 0]];

CounterShape()->print($bindings);

// 处理 HTMX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/increment') {
    $count = (int)($_COOKIE['count'] ?? 0) + 1;
    setcookie('count', $count);

    CountShape()->print(['count' => $count]);
    exit;
}
```

### 3. 无限滚动列表

```php
<?php

use function Pure\HTML\{div, ul, li};

function TodoList() {
    return div(
        ul()->id('todos'),
        button('Load More')
            ->hxGet('/todos?page=1')
            ->hxTarget('#todos')
            ->hxSwap('beforeend')
    )->class('todo-list');
}

// 处理 HTMX 请求
if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/todos') === 0) {
    $page = (int)($_GET['page'] ?? 1);
    $todos = getTodos($page); // 获取待办事项列表

    foreach ($todos as $todo) {
        echo li($todo['title'])->class('todo-item');
    }
    exit;
}
```

### 4. 实时搜索

结果列表是一个形状；每个结果标题都用 `Slot::text()` 绑定，端点用搜索结果渲染列表形状：

```php
<?php

use Pure\Compile\{Compile, Shape};
use Pure\Core\Slot;

use function Pure\HTML\{input, div};

function SearchResultShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(Slot::text('title'))->class('search-result')
    );
}

function ResultListShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(Slot::each('results', SearchResultShape()))
    );
}

function SearchBoxShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            input()
                ->type('text')
                ->placeholder('Search...')
                ->hxGet('/search')
                ->hxTrigger('keyup changed delay:500ms')
                ->hxTarget('#results'),
            div(Slot::child('list', ResultListShape()))->id('results')
        )->class('search-box')
    );
}

// 用空结果列表渲染页面
$bindings = ['list' => ['results' => []]];

SearchBoxShape()->print($bindings);

// 处理 HTMX 请求
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/search') {
    $query = $_GET['q'] ?? '';
    $results = searchItems($query); // 搜索项目

    ResultListShape()->print(['results' => $results]);
    exit;
}
```



## 下一步

- [HTMX 文档](https://htmx.org/docs/)
- [PurePHP 组件指南](/zh/guide/components)
- [PurePHP 事件处理](/zh/guide/events)
