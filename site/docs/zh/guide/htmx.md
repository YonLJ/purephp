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

计数器文本是绑定的槽位；端点用新的计数渲染同一个 `CounterValue()` 组件。模板是 shape 文件：

```php
<?php

// Count.shape.php
return Compile::shape(
    p('Current count: ', Slot::text('count'))->id('counter')
);

// Counter.shape.php
return Compile::shape(
    div(
        Slot::raw('counter'),
        button('Increment')
            ->hxPost('/increment')
            ->hxTarget('#counter')
            ->hxSwap('innerHTML')
    )->class('counter')
);
```

```php
<?php

use Pure\Core\Raw;

use function Pure\HTML\{button, div, p};
use function Pure\Component\render;

function CounterValue(int $count): Raw
{
    return render(__DIR__ . '/Count.shape.php', count: $count);
}

function Counter(int $count): Raw
{
    return render(__DIR__ . '/Counter.shape.php', counter: (string) CounterValue($count));
}

// 渲染页面
echo Counter(0);

// 处理 HTMX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/increment') {
    $count = (int)($_COOKIE['count'] ?? 0) + 1;
    setcookie('count', $count);

    echo CounterValue($count);
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

结果列表是一个组件；每个结果标题都用 `Slot::text()` 绑定，端点用搜索结果渲染列表组件：

```php
<?php

// SearchResult.shape.php
return Compile::shape(
    div(Slot::text('title'))->class('search-result')
);

// ResultList.shape.php
return Compile::shape(div(Slot::raw('results')));

// SearchBox.shape.php
return Compile::shape(
    div(
        input()
            ->type('text')
            ->placeholder('Search...')
            ->hxGet('/search')
            ->hxTrigger('keyup changed delay:500ms')
            ->hxTarget('#results'),
        div(Slot::raw('list'))->id('results')
    )->class('search-box')
);
```

```php
<?php

use Pure\Core\Raw;

use function Pure\HTML\{div, input};
use function Pure\Component\render;

function SearchResult(string $title): Raw
{
    return render(__DIR__ . '/SearchResult.shape.php', title: $title);
}

function ResultList(array $results): Raw
{
    $items = [];

    foreach ($results as $result) {
        $items[] = (string) SearchResult($result['title']);
    }

    return render(__DIR__ . '/ResultList.shape.php', results: implode('', $items));
}

function SearchBox(Raw $list): Raw
{
    return render(__DIR__ . '/SearchBox.shape.php', list: $list);
}

// 用空结果列表渲染页面
echo SearchBox(ResultList([]));

// 处理 HTMX 请求
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/search') {
    $query = $_GET['q'] ?? '';
    $results = searchItems($query); // 搜索项目

    echo ResultList($results);
    exit;
}
```



## 下一步

- [HTMX 文档](https://htmx.org/docs/)
- [PurePHP 组件指南](/zh/guide/components)
- [PurePHP 事件处理](/zh/guide/events)
