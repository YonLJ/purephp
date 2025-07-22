# 事件

本指南解释如何在 PurePHP 组件中处理事件。

## 基本事件处理

PurePHP 通过属性方法支持所有标准 HTML 事件。事件处理程序通常是作为字符串传递的 JavaScript 函数：

```php
<?php

use function Pure\HTML\{div, button, input};

function BasicEvents() {
    return div(
        button('点击我')
            ->onclick('handleClick()')
            ->class('btn'),
        input()
            ->type('text')
            ->oninput('handleInput(event)')
            ->placeholder('输入一些内容...')
            ->class('input')
    )->class('events-container');
}

// 使用组件
BasicEvents()->toPrint();
```

## 鼠标事件

处理各种鼠标交互：

```php
<?php

use function Pure\HTML\div;

function MouseEvents() {
    return div('悬停并点击我！')
        ->onclick('console.log("点击了！")')
        ->onmouseover('this.style.backgroundColor = "#f0f0f0"')
        ->onmouseout('this.style.backgroundColor = ""')
        ->onmousedown('this.style.transform = "scale(0.95)"')
        ->onmouseup('this.style.transform = "scale(1)"')
        ->style('padding: 20px; border: 1px solid #ccc; cursor: pointer; transition: all 0.2s;')
        ->class('mouse-events');
}

// 使用组件
MouseEvents()->toPrint();
```

## 键盘事件

处理键盘输入：

```php
<?php

use function Pure\HTML\{div, input, p};

function KeyboardEvents() {
    return div(
        p('在下面的输入框中输入：'),
        input()
            ->type('text')
            ->onkeydown('handleKeyDown(event)')
            ->onkeyup('handleKeyUp(event)')
            ->oninput('handleInput(event)')
            ->placeholder('按键...')
            ->class('keyboard-input'),
        p()->id('key-display')->style('margin-top: 10px; font-family: monospace;')
    )->class('keyboard-events');
}

// 使用带 JavaScript 的组件
echo KeyboardEvents();
?>
<script>
function handleKeyDown(event) {
    document.getElementById('key-display').textContent =
        `按下的键: ${event.key} (代码: ${event.code})`;
}

function handleKeyUp(event) {
    console.log('释放的键:', event.key);
}

function handleInput(event) {
    console.log('输入值:', event.target.value);
}
</script>
```

## 表单事件

处理表单交互：

```php
<?php

use function Pure\HTML\{form, div, label, input, button, p};

function FormEvents() {
    return div(
        form(
            div(
                label('用户名:')->for('username'),
                input()
                    ->type('text')
                    ->id('username')
                    ->name('username')
                    ->onchange('handleChange(event)')
                    ->required()
            )->class('form-group'),
            div(
                label('邮箱:')->for('email'),
                input()
                    ->type('email')
                    ->id('email')
                    ->name('email')
                    ->onchange('handleChange(event)')
                    ->required()
            )->class('form-group'),
            button('提交')
                ->type('submit')
                ->class('submit-btn')
        )
        ->onsubmit('handleFormSubmit(event)')
        ->class('event-form'),
        p()->id('form-status')->style('margin-top: 10px; color: #666;')
    )->class('form-events');
}

// 使用带 JavaScript 的组件
echo FormEvents();
?>
<script>
function handleChange(event) {
    console.log(`${event.target.name} 改变为: ${event.target.value}`);
}

function handleFormSubmit(event) {
    event.preventDefault(); // 阻止实际表单提交
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);

    document.getElementById('form-status').textContent =
        `表单提交数据: ${JSON.stringify(data)}`;

    console.log('表单数据:', data);
}
</script>
```

## 事件委托

使用事件委托高效处理事件：

```php
<?php

use function Pure\HTML\{div, button};

function EventDelegation() {
    $buttons = [];
    for ($i = 1; $i <= 5; $i++) {
        $buttons[] = button("按钮 {$i}")
            ->data_id($i)
            ->class('delegated-btn');
    }

    return div(
        div('点击任意按钮:')->style('margin-bottom: 10px;'),
        div(...$buttons)->class('button-group'),
        div()->id('delegation-output')->style('margin-top: 10px; color: #666;')
    )
    ->onclick('handleDelegatedClick(event)')
    ->class('delegation-container');
}

// 使用带 JavaScript 的组件
echo EventDelegation();
?>
<script>
function handleDelegatedClick(event) {
    if (event.target.classList.contains('delegated-btn')) {
        const buttonId = event.target.dataset.id;
        document.getElementById('delegation-output').textContent =
            `点击了按钮 ${buttonId}`;
        console.log('点击的按钮:', buttonId);
    }
}
</script>
```

## 组件事件通信

在组件之间传递事件处理程序：

```php
<?php

use function Pure\HTML\{div, button, p};

function ParentComponent() {
    return div(
        p('父组件'),
        ChildComponent([
            'onButtonClick' => 'handleChildClick',
            'message' => '从子组件点击我！'
        ]),
        p()->id('parent-output')->style('margin-top: 10px; color: #666;')
    )->class('parent-component');
}

function ChildComponent($props) {
    [
        'onButtonClick' => $onButtonClick,
        'message' => $message
    ] = $props;

    return div(
        p('子组件'),
        button($message)
            ->onclick("{$onButtonClick}('来自子组件的问候！')")
            ->class('child-btn')
    )->class('child-component')->style('border: 1px solid #ddd; padding: 10px; margin: 10px 0;');
}

// 使用带 JavaScript 的组件
echo ParentComponent();
?>
<script>
function handleChildClick(message) {
    document.getElementById('parent-output').textContent =
        `从子组件接收到: ${message}`;
    console.log('子组件事件:', message);
}
</script>
```

## 自定义事件属性

处理任何 HTML 事件属性：

```php
<?php

use function Pure\HTML\{div, img};

function CustomEventAttributes() {
    return div(
        div('带加载事件的图片:'),
        img()
            ->src('https://via.placeholder.com/200x100')
            ->alt('占位符图片')
            ->onload('console.log("图片已加载！")')
            ->onerror('console.log("图片加载失败")')
            ->style('display: block; margin: 10px 0;'),

        div('带焦点事件的 Div:'),
        div('点击获得焦点，然后按 Tab')
            ->tabindex('0')
            ->onfocus('this.style.outline = "2px solid blue"')
            ->onblur('this.style.outline = "none"')
            ->style('padding: 10px; border: 1px solid #ccc; margin: 10px 0;')
    )->class('custom-events');
}

// 使用组件
CustomEventAttributes()->toPrint();
```

## 事件处理最佳实践

### 1. 内联 vs 外部处理程序

```php
<?php

use function Pure\HTML\{div, button};

// 内联处理程序（适合简单操作）
$inlineButton = button('内联处理程序')
    ->onclick('alert("来自内联的问候！")')
    ->class('btn');

// 外部处理程序（适合复杂逻辑）
$externalButton = button('外部处理程序')
    ->onclick('handleComplexAction()')
    ->class('btn');

div(
    $inlineButton,
    $externalButton
)->toPrint();
?>
<script>
function handleComplexAction() {
    // 复杂逻辑在这里
    console.log('执行复杂操作');
    // ... 更多代码
}
</script>
```

### 2. 事件对象使用

```php
<?php

use function Pure\HTML\{div, button};

function EventObjectExample() {
    return div(
        button('获取事件信息')
            ->onclick('showEventInfo(event)')
            ->class('btn'),
        div()->id('event-info')->style('margin-top: 10px; font-family: monospace;')
    )->class('event-object-example');
}

echo EventObjectExample();
?>
<script>
function showEventInfo(event) {
    const info = `
        事件类型: ${event.type}
        目标: ${event.target.tagName}
        时间戳: ${event.timeStamp}
        坐标: (${event.clientX}, ${event.clientY})
    `;
    document.getElementById('event-info').textContent = info;
}
</script>
```

## 下一步

- [HTMX 集成](/zh/guide/htmx) - 了解如何与 HTMX 配合使用
- [组件](/zh/guide/components) - 深入学习组件开发
- [属性](/zh/guide/props) - 了解属性系统
