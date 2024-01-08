<?php declare(strict_types=1);
require_once '../../vendor/autoload.php';

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

function Address(array $props)
{
    extract($props);

    return (
        XML::address(
            empty($street) ? null : XML::street($street),
            empty($city)   ? null : XML::city($city),
            empty($state)  ? null : XML::state($state),
            empty($zip)    ? null : XML::zip($zip)
        )
    );
}

/**
 * @var XML
 */
$xml = (
    XML::customers(
        XML::customer(
            XML::name('Charter Group'),
            array_map(fn($x) => Address($x), $data)
        )->id('55000')
    )

);

$xml->save('./example.xml');
