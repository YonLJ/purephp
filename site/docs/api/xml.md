# XML Class

`Pure\Core\XML` extends the Tag class for creating XML elements.

## Creating XML Elements

XML tag names are arbitrary, so elements are created with the magic static
surface:

```php
<?php

use Pure\Core\XML;

// Create XML elements with magic methods
$customer = XML::customer(
    XML::name('Customer Name'),
    XML::address(
        XML::street('Street Address'),
        XML::city('City'),
        XML::zip('Zip Code')
    )
)->id('123');
```

## Save Methods

### `save(string $path, ?string $header = null): int|false`

Saves the XML element to a file. When `$header` is omitted,
`<?xml version="1.0"?>` is written first.

```php
<?php

use Pure\Core\XML;

$xml = XML::root(
    XML::item('Content 1'),
    XML::item('Content 2')
);

$result = $xml->save('output.xml');
if ($result !== false) {
    echo "XML file saved successfully";
}
```

## Examples

### Configuration Files

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

### Data Export

```php
<?php

use Pure\Core\XML;

function exportUsers(array $users): XML
{
    $userElements = [];
    
    foreach ($users as $userData) {
        $addressElements = [];
        if (!empty($userData['addresses'])) {
            foreach ($userData['addresses'] as $addr) {
                $addressElements[] = XML::address(
                    XML::street($addr['street']),
                    XML::city($addr['city']),
                    XML::state($addr['state']),
                    XML::zip($addr['zip'])
                )->type($addr['type']);
            }
        }
        
        $userElements[] = XML::user(
            XML::name($userData['name']),
            XML::email($userData['email']),
            XML::role($userData['role']),
            XML::created($userData['created_at']),
            !empty($addressElements) ? XML::addresses(...$addressElements) : null
        )->id($userData['id']);
    }
    
    return XML::users(...$userElements);
}

$users = [
    [
        'id' => '1',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'admin',
        'created_at' => '2024-01-01',
        'addresses' => [
            [
                'type' => 'home',
                'street' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zip' => '12345'
            ]
        ]
    ]
];

$xml = exportUsers($users);
$xml->save('users.xml');
```

### RSS Feed

```php
<?php

use Pure\Core\XML;

function createRSSFeed(array $items): XML
{
    return XML::rss(
        XML::channel(
            XML::title('My Blog'),
            XML::link('https://myblog.com'),
            XML::description('Latest posts from my blog'),
            XML::language('en-us'),
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
        'title' => 'First Post',
        'url' => 'https://myblog.com/first-post',
        'description' => 'This is my first blog post',
        'date' => '2024-01-01 12:00:00'
    ]
];

$rss = createRSSFeed($posts);
$rss->save('feed.xml');
```

### SOAP Envelope

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

### Large Documents

```php
<?php

use Pure\Core\XML;

$items = [];
for ($i = 1; $i <= 10000; $i++) {
    $items[] = XML::item("Item $i")->id((string)$i);
}

$largeXml = XML::root(...$items);
$largeXml->save('large.xml');
```
