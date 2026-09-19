<?php declare(strict_types=1);

/**
 * The data access of the pricing page: the records the page renders.
 *
 * The records are plain PHP here, a stand-in for the database or API a real
 * application would call; the components never see this file, they ask
 * PricingService for their own slice.
 *
 * @return array{
 *     header: array{
 *         navs: list<array{text: string, href: string, class: string}>,
 *         signUp: array{text: string, href: string, class: string}
 *     },
 *     pricing: array{title: string, desc: string},
 *     deck: list<array{type: string, price: string, features: list<array{value: string}>, text: string, class: string}>,
 *     footer: array{
 *         logo: array{src: string, width: string, height: string, text: string},
 *         links: list<array{title: string, links: list<array{text: string, href: string}>}>
 *     }
 * }
 */
final class PricingDao
{
    /**
     * @return array<string, mixed>
     */
    public static function content(): array
    {
        static $content;

        if ($content !== null) {
            return $content;
        }

        $navHref = 'https://getbootstrap.com/docs/4.0/examples/pricing/#';

        return $content = [
            'header' => [
                'navs' => [
                    ['text' => 'Features',   'href' => $navHref, 'class' => 'p-2 text-dark'],
                    ['text' => 'Enterprise', 'href' => $navHref, 'class' => 'p-2 text-dark'],
                    ['text' => 'Support',    'href' => $navHref, 'class' => 'p-2 text-dark'],
                    ['text' => 'Pricing',    'href' => $navHref, 'class' => 'p-2 text-dark'],
                ],
                'signUp' => [
                    'text' => 'Sign up',
                    'href' => $navHref,
                    'class' => 'btn btn-outline-primary',
                ],
            ],
            'pricing' => [
                'title' => 'Pricing',
                'desc' => "Quickly build an effective pricing table for your potential customers with this Bootstrap example. It's built with default Bootstrap components and utilities with little customization.",
            ],
            'deck' => [
                'cards' => [
                    [
                        'type' => 'Free',
                        'price' => '0',
                        'features' => [
                            ['value' => '10 users included'],
                            ['value' => '2 GB of storage'],
                            ['value' => 'Email support'],
                            ['value' => 'Help center access'],
                        ],
                        'text' => 'Sign up for free',
                        'class' => 'btn btn-lg btn-block btn-outline-primary',
                    ],
                    [
                        'type' => 'Pro',
                        'price' => '15',
                        'features' => [
                            ['value' => '20 users included'],
                            ['value' => '10 GB of storage'],
                            ['value' => 'Priority email support'],
                            ['value' => 'Help center access'],
                        ],
                        'text' => 'Get started',
                        'class' => 'btn btn-lg btn-block btn-primary',
                    ],
                    [
                        'type' => 'Enterprise',
                        'price' => '29',
                        'features' => [
                            ['value' => '30 users included'],
                            ['value' => '15 GB of storage'],
                            ['value' => 'Phone and email support'],
                            ['value' => 'Help center access'],
                        ],
                        'text' => 'Contact us',
                        'class' => 'btn btn-lg btn-block btn-primary',
                    ],
                ],
            ],
            'footer' => [
                'logo' => [
                    'src' => 'https://getbootstrap.com/docs/4.0/assets/brand/bootstrap-solid.svg',
                    'width' => '24',
                    'height' => '24',
                    'text' => '© 2017-2018',
                ],
                'links' => [
                    [
                        'title' => 'Features',
                        'links' => [
                            ['text' => 'Cool stuff', 'href' => '#'],
                            ['text' => 'Random feature', 'href' => '#'],
                            ['text' => 'Team feature', 'href' => '#'],
                            ['text' => 'Stuff for developers', 'href' => '#'],
                            ['text' => 'Another one', 'href' => '#'],
                            ['text' => 'Last time', 'href' => '#'],
                        ],
                    ],
                    [
                        'title' => 'Resources',
                        'links' => [
                            ['text' => 'Resource', 'href' => '#'],
                            ['text' => 'Resource name', 'href' => '#'],
                            ['text' => 'Another resource', 'href' => '#'],
                            ['text' => 'Final resource', 'href' => '#'],
                        ],
                    ],
                    [
                        'title' => 'About',
                        'links' => [
                            ['text' => 'Team', 'href' => '#'],
                            ['text' => 'Locations', 'href' => '#'],
                            ['text' => 'Privacy', 'href' => '#'],
                            ['text' => 'Terms', 'href' => '#'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
