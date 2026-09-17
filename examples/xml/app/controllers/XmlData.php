<?php declare(strict_types=1);

/**
 * The view data of the xml page, keyed by the slots of views/xml.cmp.php.
 *
 * @return array<string, mixed>
 */
function xmlData(): array
{
    return [
        'addresses' => [
            [
                'street' => '100 Main',
                'city'   => 'Framingham',
                'state'  => 'MA',
                'zip'    => '01701',
            ],
            [
                'street' => '720 Prospect',
                'city'   => 'Framingham',
                'state'  => 'MA',
                'zip'    => '01701',
            ],
            [
                'street' => '120 Ridge',
                'state'  => 'MA',
                'zip'    => '01760',
            ],
        ],
    ];
}
