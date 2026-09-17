# XML 类

`Pure\Core\XML` 继承自 Tag 类，用于创建 XML 元素。

## 创建 XML 元素

XML 标签名是任意的，因此元素通过魔术静态接口创建：

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

## 保存方法

### `save(string $path, ?string $header = null): int|false`

将 XML 元素保存到文件。省略 `$header` 时会先写入 `<?xml version="1.0"?>`。

```php
<?php

use Pure\Core\XML;

$xml = XML::root(
    XML::item('内容1'),
    XML::item('内容2')
);

$result = $xml->save('output.xml');
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

$config->save('config.xml');
```

### 数据导出

```php
<?php

use Pure\Core\XML;

function exportUsers(array $users): XML
{
    $userElements = [];

    foreach ($users as $userData) {
        $userElements[] = XML::user(
            XML::name($userData['name']),
            XML::email($userData['email']),
            XML::role($userData['role']),
            XML::created($userData['created_at'])
        )->id($userData['id']);
    }

    return XML::users(...$userElements);
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
$xml->save('users.xml');
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
$rss->save('feed.xml');
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

### 大型文档

```php
<?php

use Pure\Core\XML;

$items = [];
for ($i = 1; $i <= 10000; $i++) {
    $items[] = XML::item("项目 $i")->id((string)$i);
}

$largeXml = XML::root(...$items);
$largeXml->save('large.xml');
```
