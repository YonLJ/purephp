<?php

require_once '../../vendor/autoload.php';

use Tiny\Tag;
use Tiny\VDom;

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

$xml = (
    Tag::customers(
        Tag::customer(
            Tag::name('Charter Group'),
            array_map(fn($x) => Address($x), $data)
        )->id('55000')
    )

);

$vDom = new VDom();
$vDom->save($xml, './example.xml');
