# XML Class

`Pure\Core\XML` extends the Tag class for creating XML elements.

## Creating XML Elements

XML elements can also be created using both approaches:

### 1. Magic Static Methods

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

### 2. Constructor Method

```php
<?php

use Pure\Core\XML;

// Create XML elements with constructor
$customer = (new XML('customer', [
    new XML('name', ['Customer Name']),
    new XML('address', [
        new XML('street', ['Street Address']),
        new XML('city', ['City']),
        new XML('zip', ['Zip Code'])
    ])
]))->id('123');
```

## Save Methods

### `toSave(string $path, string $header = '<?xml version="1.0"?>'): int|false`

Saves the XML element to a file.

```php
<?php

use Pure\Core\XML;

// Using magic static methods
$xml = XML::root(
    XML::item('Content 1'),
    XML::item('Content 2')
);

// Or using constructor
$xml = new XML('root', [
    new XML('item', ['Content 1']),
    new XML('item', ['Content 2'])
]);

$result = $xml->toSave('output.xml');
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

$config->toSave('config.xml');
```

### Data Export

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
        
        if (!empty($userData['addresses'])) {
            $addresses = XML::addresses();
            foreach ($userData['addresses'] as $addr) {
                $address = XML::address(
                    XML::street($addr['street']),
                    XML::city($addr['city']),
                    XML::state($addr['state']),
                    XML::zip($addr['zip'])
                )->type($addr['type']);
                $addresses = $addresses->appendChild($address);
            }
            $user = $user->appendChild($addresses);
        }
        
        $usersXml = $usersXml->appendChild($user);
    }
    
    return $usersXml;
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
$xml->toSave('users.xml');
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
$rss->toSave('feed.xml');
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

### Custom XML with Constructor

```php
<?php

use Pure\Core\XML;

// For performance-critical XML generation
$largeXml = new XML('root');
for ($i = 1; $i <= 10000; $i++) {
    $item = new XML('item', ["Item $i"]);
    $item->id((string)$i);
    $largeXml = $largeXml->appendChild($item);
}

$largeXml->toSave('large.xml');
```
