<?php declare(strict_types=1);

require_once '../../vendor/autoload.php';

use Tiny\Core\Tag;

$data = [
    [
        'street' => '100 Main',
        'city'   => 'Framingham',
        'state'  => 'MA',
        'zip'    => '01701'
    ],
    [
        'street' => '720 Prospect',
        'city'   => 'Framingham',
        'state'  => 'MA',
        'zip'    => '01701'
    ],
    [
        'street' => '120 Ridge',
        'state'  => 'MA',
        'zip'    => '01760'
    ]
];

function Address($props)
{
    extract($props);
    return (
        Tag::address(
            empty($street) ? null : Tag::street($street),
            empty($city)   ? null : Tag::city($city),
            empty($state)  ? null : Tag::state($state),
            empty($zip)    ? null : Tag::zip($zip)
        )
    );
}

/**
 * @var Tag
 */
$xml = (
    Tag::customers(
        Tag::customer(
            Tag::name('Charter Group'),
            array_map(fn($x) => Address($x), $data)
        )->id('55000')
    )

);

$xml->save('./example.xml', true);
