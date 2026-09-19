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
composer require yonld/purephp
```

然后在 HTML 中引入 HTMX：

```html
<script src="https://unpkg.com/htmx.org@2.0.4"></script>
```

### 2. 创建动态组件

计数器文本是绑定的槽位；端点用新的计数渲染同一个 `CounterValue()` 组件。每个组件都是自己的单元：

```php
<?php

// components/CounterValue.cmp.php
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\p;

register('CounterValue', __FILE__, static fn () =>
    p('Current count: ', Slot::value('count'))->id('counter')
);

function CounterValue(int $count): string
{
    return render('CounterValue', count: $count);
}
```
```php
<?php

// index.php

// 渲染页面
echo Counter(0);

// Handle HTMX request
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

结果列表是一个组件；每个结果标题都用 `Slot::value()` 绑定，端点用搜索结果渲染列表组件：

```php
<?php

// components/SearchResult.cmp.php
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\div;

register('SearchResult', __FILE__, static fn () =>
    div(Slot::value('title'))->class('search-result')
);

function SearchResult(string $title): string
{
    return render('SearchResult', title: $title);
}
```
```php
<?php

// components/ResultList.cmp.php
require_once __DIR__ . '/SearchResult.cmp.php';

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\div;

register('ResultList', __FILE__, static fn () => div(Slot::raw('results')));

function ResultList(array $results): string
{
    $items = [];

    foreach ($results as $result) {
        $items[] = SearchResult($result['title']);
    }

    return render('ResultList', results: implode('', $items));
}
```
```php
<?php

// components/SearchBox.cmp.php
require_once __DIR__ . '/ResultList.cmp.php';

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, input};

register('SearchBox', __FILE__, static fn () =>
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

function SearchBox(iterable|string $list): string
{
    return render('SearchBox', list: $list);
}
```
```php
<?php

// index.php

// 用空结果列表渲染页面
echo SearchBox(ResultList([]));

// Handle HTMX request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/search') {
    $query = $_GET['q'] ?? '';
    $results = searchItems($query); // Search items

    echo ResultList($results);
    exit;
}
```

## 下一步

- [HTMX 文档](https://htmx.org/docs/)
- [PurePHP 组件指南](/zh/guide/components)
- [PurePHP 事件处理](/zh/guide/events)
