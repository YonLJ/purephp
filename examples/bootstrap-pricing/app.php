<?php declare(strict_types=1);

require_once '../../vendor/autoload.php';
require_once './components/PageHeader.php';
require_once './components/PricingHeader.php';
require_once './components/CardDeck.php';
require_once './components/PageFooter.php';
require_once './components/ColLogo.php';
require_once './components/ColLinks.php';
require_once './components/Container.php';

use function Pure\Tags\HTML\body;

body(
    PageHeader([
        'companyName' => 'Company name',
        'navs' => [
            ['text' => 'Features',   'href' => 'https://getbootstrap.com/docs/4.0/examples/pricing/#'],
            ['text' => 'Enterprise', 'href' => 'https://getbootstrap.com/docs/4.0/examples/pricing/#'],
            ['text' => 'Support',    'href' => 'https://getbootstrap.com/docs/4.0/examples/pricing/#'],
            ['text' => 'Pricing',    'href' => 'https://getbootstrap.com/docs/4.0/examples/pricing/#'],
        ],
        'signUp' => [
            'text' => 'Sign up',
            'href' => 'https://getbootstrap.com/docs/4.0/examples/pricing/#'
        ]
    ]),
    PricingHeader([
        'title' => 'Pricing',
        'desc' => "Quickly build an effective pricing table for your potential customers with this Bootstrap example. It's built with default Bootstrap components and utilities with little customization."
    ]),
    Container(
        CardDeck(
            [
                [
                    'type' => 'Free',
                    'price' => '0',
                    'features' => [
                        '10 users included',
                        '2 GB of storage',
                        'Email support',
                        'Help center access'
                    ],
                    'button' => [
                        'text' => 'Sign up for free',
                        'class' => 'btn-outline-primary'
                    ]
                ],
                [
                    'type' => 'Pro',
                    'price' => '15',
                    'features' => [
                        '20 users included',
                        '10 GB of storage',
                        'Priority email support',
                        'Help center access'
                    ],
                    'button' => [
                        'text' => 'Get started',
                        'class' => 'btn-primary'
                    ]
                ],
                [
                    'type' => 'Enterprise',
                    'price' => '29',
                    'features' => [
                        '30 users included',
                        '15 GB of storage',
                        'Phone and email support',
                        'Help center access'
                    ],
                    'button' => [
                        'text' => 'Contact us',
                        'class' => 'btn-primary'
                    ]
                ]
            ]
        ),
        PageFooter(
            ColLogo([
                'img' => [
                    'src' => 'https://getbootstrap.com/docs/4.0/assets/brand/bootstrap-solid.svg',
                    'width' => '24',
                    'height' => '24'
                ],
                'text' => '© 2017-2018'
            ]),
            array_map(fn($data) => ColLinks($data), [
                [
                    'title' => 'Features',
                    'links' => [
                        ['text' => 'Cool stuff', 'href' => '#'],
                        ['text' => 'Random feature', 'href' => '#'],
                        ['text' => 'Team feature', 'href' => '#'],
                        ['text' => 'Stuff for developers', 'href' => '#'],
                        ['text' => 'Another one', 'href' => '#'],
                        ['text' => 'Last time', 'href' => '#']
                    ]
                ],
                [
                    'title' => 'Resources',
                    'links' => [
                        ['text' => 'Resource', 'href' => '#'],
                        ['text' => 'Resource name', 'href' => '#'],
                        ['text' => 'Another resource', 'href' => '#'],
                        ['text' => 'Final resource', 'href' => '#'],
                    ]
                ],
                [
                    'title' => 'About',
                    'links' => [
                        ['text' => 'Team', 'href' => '#'],
                        ['text' => 'Locations', 'href' => '#'],
                        ['text' => 'Privacy', 'href' => '#'],
                        ['text' => 'Terms', 'href' => '#'],
                    ]
                ]
            ])
        )
    )
)->toPrint();
