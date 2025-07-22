# XML 类

`Pure\Core\XML` 继承自 Tag 类，用于创建 XML 元素。

## 创建 XML 元素

XML 元素也可以使用两种方式创建：

### 1. 魔术静态方法

```php
<?php

use Pure\Core\XML;

// 使用魔术方法创建 XML 元素
$customer = XML::customer(
    XML::name('客户名称'),
    XML::address(
        XML::street('街道地址'),
        XML::city('城市'),
        XML::zip('邮编')
    )
)->id('123');
```

### 2. 构造函数方法

```php
<?php

use Pure\Core\XML;

// 使用构造函数创建 XML 元素
$customer = (new XML('customer', [
    new XML('name', ['客户名称']),
    new XML('address', [
        new XML('street', ['街道地址']),
        new XML('city', ['城市']),
        new XML('zip', ['邮编'])
    ])
]))->id('123');
```

## 保存方法

### `toSave(string $path, string $header = '<?xml version="1.0"?>'): int|false`

将 XML 元素保存到文件。

```php
<?php

use Pure\Core\XML;

// 使用魔术静态方法
$xml = XML::root(
    XML::item('内容1'),
    XML::item('内容2')
);

// 或使用构造函数
$xml = new XML('root', [
    new XML('item', ['内容1']),
    new XML('item', ['内容2'])
]);

$result = $xml->toSave('output.xml');
if ($result !== false) {
    echo "XML 文件保存成功";
}
```

## 示例

### 配置文件

```php
<?php

use Pure\Core\XML;

$config = XML::configuration(
    XML::database(
        XML::host('localhost'),
        XML::port('3306'),
        XML::name('myapp'),
        XML::username('user'),
        XML::password('pass')
    ),
    XML::cache(
        XML::enabled('true'),
        XML::ttl('3600')
    ),
    XML::logging(
        XML::level('info'),
        XML::file('/var/log/app.log')
    )
)->version('1.0');

$config->toSave('config.xml');
```

### 数据导出

```php
<?php

use Pure\Core\XML;

function exportUsers(array $users): XML
{
    $usersXml = XML::users();

    foreach ($users as $userData) {
        $user = XML::user(
            XML::name($userData['name']),
            XML::email($userData['email']),
            XML::role($userData['role']),
            XML::created($userData['created_at'])
        )->id($userData['id']);

        $usersXml = $usersXml->appendChild($user);
    }

    return $usersXml;
}

$users = [
    [
        'id' => '1',
        'name' => '张三',
        'email' => 'zhangsan@example.com',
        'role' => 'admin',
        'created_at' => '2024-01-01'
    ]
];

$xml = exportUsers($users);
$xml->toSave('users.xml');
```

### RSS 订阅

```php
<?php

use Pure\Core\XML;

function createRSSFeed(array $items): XML
{
    return XML::rss(
        XML::channel(
            XML::title('我的博客'),
            XML::link('https://myblog.com'),
            XML::description('我的博客最新文章'),
            XML::language('zh-cn'),
            XML::pubDate(date('r')),
            ...array_map(function($item) {
                return XML::item(
                    XML::title($item['title']),
                    XML::link($item['url']),
                    XML::description($item['description']),
                    XML::pubDate($item['date']),
                    XML::guid($item['url'])
                );
            }, $items)
        )
    )->version('2.0');
}

$posts = [
    [
        'title' => '第一篇文章',
        'url' => 'https://myblog.com/first-post',
        'description' => '这是我的第一篇博客文章',
        'date' => '2024-01-01 12:00:00'
    ]
];

$rss = createRSSFeed($posts);
$rss->toSave('feed.xml');
```

### SOAP 信封

```php
<?php

use Pure\Core\XML;

$soapEnvelope = XML::envelope(
    XML::header(
        XML::authentication(
            XML::username('user'),
            XML::password('pass')
        )
    ),
    XML::body(
        XML::getUserRequest(
            XML::userId('123')
        )
    )
)->xmlns_soap('http://schemas.xmlsoap.org/soap/envelope/');

echo $soapEnvelope;
```

### 使用构造函数的自定义 XML

```php
<?php

use Pure\Core\XML;

// 对于性能关键的 XML 生成
$largeXml = new XML('root');
for ($i = 1; $i <= 10000; $i++) {
    $item = new XML('item', ["项目 $i"]);
    $item->id((string)$i);
    $largeXml = $largeXml->appendChild($item);
}

$largeXml->toSave('large.xml');
```
