# PurePHP 与 HTMX 集成

PurePHP 和 HTMX 是一个强大的组合，可以让你在保持 PHP 后端简洁的同时，实现动态的、响应式的用户界面。

## 为什么选择这个组合？

- **PurePHP**: 提供组件化的 PHP 模板渲染
- **HTMX**: 提供无 JavaScript 的动态交互能力
- **完美互补**: PurePHP 处理服务端渲染，HTMX 处理客户端交互

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

```php
<?php

use function Pure\HTML\{div, button, p};

function Counter() {
    return div(
        p('当前计数: 0')->id('counter'),
        button('增加')
            ->hxPost('/increment')
            ->hxTarget('#counter')
            ->hxSwap('innerHTML')
    )->class('counter');
}

// 处理 HTMX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/increment') {
    $count = (int)($_COOKIE['count'] ?? 0) + 1;
    setcookie('count', $count);
    echo "当前计数: {$count}";
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
        button('加载更多')
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

```php
<?php

use function Pure\HTML\{input, div};

function SearchBox() {
    return div(
        input()
            ->type('text')
            ->placeholder('搜索...')
            ->hxGet('/search')
            ->hxTrigger('keyup changed delay:500ms')
            ->hxTarget('#results'),
        div()->id('results')
    )->class('search-box');
}

// 处理 HTMX 请求
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/search') {
    $query = $_GET['q'] ?? '';
    $results = searchItems($query); // 搜索项目

    foreach ($results as $result) {
        echo div($result['title'])->class('search-result');
    }
    exit;
}
```



## 示例项目

查看我们的 [示例项目](https://github.com/yourusername/purephp-htmx-example) 获取更多使用场景和最佳实践。

## 下一步

- [HTMX 文档](https://htmx.org/docs/)
- [PurePHP 组件指南](/zh/guide/components)
- [PurePHP 事件处理](/zh/guide/events)
