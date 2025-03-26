# HTML 标签 API

PurePHP 提供了所有标准 HTML 标签的函数。每个函数都返回一个 HTML 对象，支持链式调用设置属性。

## 基本用法

```php
<?php

use function Pure\HTML\{div, p};

// 创建元素
div('内容')->toPrint();

// 设置属性
div('内容')
    ->class('container')
    ->style('background: #fff;')
    ->toPrint();

// 嵌套元素
div(
    p('第一段'),
    p('第二段')
)->class('content')->toPrint();
```

## 可用标签

### 文档结构标签

- `html()` - HTML 根元素
- `head()` - 文档头部
- `body()` - 文档主体
- `title()` - 文档标题
- `meta()` - 元数据
- `link()` - 外部资源链接
- `style()` - 样式定义
- `script()` - JavaScript 代码

### 文本标签

- `h1()` 到 `h6()` - 标题
- `p()` - 段落
- `span()` - 行内文本
- `div()` - 块级容器
- `br()` - 换行
- `hr()` - 水平线
- `strong()` - 加粗文本
- `em()` - 强调文本
- `mark()` - 标记文本
- `small()` - 小字体文本
- `sub()` - 下标
- `sup()` - 上标

### 链接和媒体

- `a()` - 链接
- `img()` - 图片
- `video()` - 视频
- `audio()` - 音频
- `source()` - 媒体源
- `track()` - 视频轨道

### 表单元素

- `form()` - 表单
- `input()` - 输入框
- `textarea()` - 文本区域
- `button()` - 按钮
- `select()` - 选择框
- `option()` - 选项
- `label()` - 标签
- `fieldset()` - 字段集
- `legend()` - 字段集标题

### 列表

- `ul()` - 无序列表
- `ol()` - 有序列表
- `li()` - 列表项
- `dl()` - 定义列表
- `dt()` - 定义术语
- `dd()` - 定义描述

### 表格

- `table()` - 表格
- `thead()` - 表头
- `tbody()` - 表体
- `tfoot()` - 表尾
- `tr()` - 表格行
- `th()` - 表头单元格
- `td()` - 表格单元格
- `caption()` - 表格标题
- `colgroup()` - 列组
- `col()` - 列

### 其他

- `nav()` - 导航
- `header()` - 页头
- `footer()` - 页脚
- `main()` - 主要内容
- `article()` - 文章
- `section()` - 区块
- `aside()` - 侧边栏
- `figure()` - 图片组
- `figcaption()` - 图片说明
- `canvas()` - 画布
- `template()` - 模板

## 属性设置

所有 HTML 标签都支持以下属性设置方法：

### 基本属性

- `id($value)` - 设置 ID
- `class($value)` - 设置类名
- `style($value)` - 设置样式
- `title($value)` - 设置标题
- `lang($value)` - 设置语言
- `dir($value)` - 设置方向
- `data_*($value)` - 设置数据属性

### 事件属性

- `onclick($value)` - 点击事件
- `onmouseover($value)` - 鼠标悬停事件
- `onmouseout($value)` - 鼠标移出事件
- `onkeydown($value)` - 按键按下事件
- `onkeyup($value)` - 按键释放事件
- `onsubmit($value)` - 表单提交事件

### 表单属性

- `name($value)` - 设置名称
- `value($value)` - 设置值
- `type($value)` - 设置类型
- `placeholder($value)` - 设置占位符
- `required()` - 设置必填
- `disabled()` - 设置禁用
- `readonly()` - 设置只读

### 链接属性

- `href($value)` - 设置链接地址
- `target($value)` - 设置目标窗口
- `rel($value)` - 设置关系
- `download($value)` - 设置下载

### 媒体属性

- `src($value)` - 设置源
- `alt($value)` - 设置替代文本
- `width($value)` - 设置宽度
- `height($value)` - 设置高度
- `controls()` - 设置控件
- `autoplay()` - 设置自动播放
- `loop()` - 设置循环

## 示例

### 创建表单

```php
<?php

use function Pure\HTML\{form, input, button, label};

form(
    div(
        label('用户名')->for('username'),
        input()
            ->type('text')
            ->id('username')
            ->name('username')
            ->required()
    )->class('form-group'),
    div(
        label('密码')->for('password'),
        input()
            ->type('password')
            ->id('password')
            ->name('password')
            ->required()
    )->class('form-group'),
    button('提交')->type('submit')
)->method('POST')->action('/login')->toPrint();
```

### 创建导航菜单

```php
<?php

use function Pure\HTML\{nav, ul, li, a};

nav(
    ul(
        li(a('首页')->href('/')),
        li(a('关于')->href('/about')),
        li(a('服务')->href('/services')),
        li(a('联系')->href('/contact'))
    )->class('nav-menu')
)->class('main-nav')->toPrint();
```

### 创建卡片组件

```php
<?php

use function Pure\HTML\{div, h2, p, a};

function Card($props) {
    [
        'title' => $title,
        'content' => $content,
        'link' => $link = null
    ] = $props;

    return div(
        h2($title),
        p($content),
        $link ? a('了解更多')->href($link) : null
    )->class('card');
}

// 使用组件
div(
    Card([
        'title' => '标题 1',
        'content' => '内容 1',
        'link' => '/more/1'
    ]),
    Card([
        'title' => '标题 2',
        'content' => '内容 2',
        'link' => '/more/2'
    ])
)->class('card-grid')->toPrint();
```
