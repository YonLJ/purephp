# 事件处理

PurePHP 提供了灵活的事件处理系统，用于处理用户交互和组件通信。

## 基本事件

### 1. 鼠标事件

```php
<?php

use function Pure\HTML\{button};

button('点击我')
    ->onclick('handleClick()')
    ->onmouseover('handleMouseOver()')
    ->onmouseout('handleMouseOut()')
    ->onmousedown('handleMouseDown()')
    ->onmouseup('handleMouseUp()')
    ->toPrint();
```

### 2. 键盘事件

```php
<?php

use function Pure\HTML\{input};

input()
    ->type('text')
    ->onkeydown('handleKeyDown(event)')
    ->onkeyup('handleKeyUp(event)')
    ->onkeypress('handleKeyPress(event)')
    ->toPrint();
```

### 3. 表单事件

```php
<?php

use function Pure\HTML\{form, input};

form()
    ->onsubmit('handleSubmit(event)')
    ->onreset('handleReset(event)')
    ->children(
        input()
            ->type('text')
            ->onchange('handleChange(event)')
            ->oninput('handleInput(event)')
    )
    ->toPrint();
```

## 事件对象

### 1. 事件参数

```php
<?php

use function Pure\HTML\{button};

button('点击我')
    ->onclick('handleClick(event)')
    ->toPrint();

// JavaScript 处理函数
?>
<script>
function handleClick(event) {
    // 阻止默认行为
    event.preventDefault();

    // 阻止事件冒泡
    event.stopPropagation();

    // 获取事件目标
    const target = event.target;

    // 获取鼠标坐标
    const x = event.clientX;
    const y = event.clientY;
}
</script>
```

### 2. 自定义事件数据

```php
<?php

use function Pure\HTML\{button};

button('点击我')
    ->onclick('handleClick(event, "custom data")')
    ->data_id('123')
    ->data_type('action')
    ->toPrint();

// JavaScript 处理函数
?>
<script>
function handleClick(event, data) {
    // 获取自定义数据
    const id = event.target.dataset.id;
    const type = event.target.dataset.type;

    console.log('事件数据:', {
        customData: data,
        id: id,
        type: type
    });
}
</script>
```

## 事件委托

### 1. 列表事件

```php
<?php

use function Pure\HTML\{ul, li};

function TodoList($props) {
    [
        'items' => $items = []
    ] = $props;

    return ul(
        ...array_map(fn($item) => li($item), $items)
    )
    ->class('todo-list')
    ->onclick('handleTodoClick(event)');
}

// JavaScript 处理函数
?>
<script>
function handleTodoClick(event) {
    // 检查点击的是否是列表项
    if (event.target.tagName === 'LI') {
        const index = Array.from(event.target.parentNode.children)
            .indexOf(event.target);

        console.log('点击的项目索引:', index);
    }
}
</script>
```

### 2. 动态内容事件

```php
<?php

use function Pure\HTML\{div};

function DynamicContent($props) {
    [
        'content' => $content
    ] = $props;

    return div($content)
        ->class('dynamic-content')
        ->onclick('handleDynamicClick(event)');
}

// JavaScript 处理函数
?>
<script>
function handleDynamicClick(event) {
    // 使用事件委托处理动态内容
    const target = event.target;

    // 处理按钮点击
    if (target.matches('.btn')) {
        handleButtonClick(target);
    }

    // 处理链接点击
    if (target.matches('a')) {
        handleLinkClick(target);
    }
}
</script>
```

## 事件处理组件

### 1. 基础事件组件

```php
<?php

use Pure\Core\HTML;

class EventComponent extends HTML {
    protected $eventHandlers = [];

    public function on($event, $handler) {
        $this->eventHandlers[$event] = $handler;
        return $this;
    }

    public function render() {
        $props = $this->props;

        // 添加事件处理器
        foreach ($this->eventHandlers as $event => $handler) {
            $props["on{$event}"] = $handler;
        }

        return new HTML('div', $props, $this->children);
    }
}

// 使用组件
$component = new EventComponent();
$component
    ->on('click', 'handleClick')
    ->on('mouseover', 'handleMouseOver')
    ->children('内容')
    ->toPrint();
```

### 2. 事件总线组件

```php
<?php

use Pure\Core\HTML;

class EventBus extends HTML {
    private static $listeners = [];

    public static function on($event, $callback) {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }
        self::$listeners[$event][] = $callback;
    }

    public static function emit($event, $data) {
        if (isset(self::$listeners[$event])) {
            foreach (self::$listeners[$event] as $callback) {
                $callback($data);
            }
        }
    }

    public function render() {
        return new HTML('div', [
            'class' => 'event-bus',
            'data-events' => json_encode(array_keys(self::$listeners))
        ], $this->children);
    }
}

// 使用事件总线
$bus = new EventBus();
$bus->children('事件总线内容')->toPrint();

// JavaScript 事件处理
?>
<script>
// 监听事件
EventBus.on('userAction', (data) => {
    console.log('用户操作:', data);
});

// 触发事件
EventBus.emit('userAction', { type: 'click', target: 'button' });
</script>
```

## 事件优化

### 1. 事件节流

```php
<?php

use function Pure\HTML\{input};

input()
    ->type('text')
    ->oninput('throttle(handleInput, 300)(event)')
    ->toPrint();

// JavaScript 节流函数
?>
<script>
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

function handleInput(event) {
    console.log('输入值:', event.target.value);
}
</script>
```

### 2. 事件防抖

```php
<?php

use function Pure\HTML\{input};

input()
    ->type('text')
    ->oninput('debounce(handleInput, 300)(event)')
    ->toPrint();

// JavaScript 防抖函数
?>
<script>
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    }
}

function handleInput(event) {
    console.log('输入值:', event.target.value);
}
</script>
```

## 最佳实践

### 1. 事件命名

- 使用动词开头
- 使用驼峰命名法
- 保持命名一致性
- 避免使用保留字

### 2. 事件处理

- 使用事件委托
- 及时移除事件监听
- 避免内存泄漏
- 优化事件性能

### 3. 事件安全

- 验证事件数据
- 防止 XSS 攻击
- 使用事件过滤
- 处理异常情况

## 下一步

- [性能优化](/guide/performance) - 优化组件性能
- [组件](/guide/components) - 深入学习组件开发
- [属性](/guide/props) - 了解属性系统
