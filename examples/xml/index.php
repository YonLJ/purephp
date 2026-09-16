<?php declare(strict_types=1);

require_once '../../vendor/autoload.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;
use Pure\Core\XML;

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

/**
 * Address shape: fields follow the data key order; `city` is optional and
 * rendered only when the record provides it.
 */
function AddressShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        XML::address(
            XML::street(Slot::text('street')),
            Slot::if('city', Compile::shape(XML::city(Slot::text('city')))),
            XML::state(Slot::text('state')),
            XML::zip(Slot::text('zip'))
        )
    );
}

$page = Compile::shape(
    XML::customers(
        XML::customer(
            XML::name('Charter Group'),
            Slot::each('addresses', AddressShape())
        )->id('55000')
    )
);

$page->compile()->save('./example.xml', ['addresses' => $data], '<?xml version="1.0"?>');
