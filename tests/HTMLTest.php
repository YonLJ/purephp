<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\HTML;

use function Pure\Core\rawHtml;
use function Pure\Tags\HTML\a;
use function Pure\Tags\HTML\body;
use function Pure\Tags\HTML\button;
use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\footer;
use function Pure\Tags\HTML\form;
use function Pure\Tags\HTML\h1;
use function Pure\Tags\HTML\h2;
use function Pure\Tags\HTML\head;
use function Pure\Tags\HTML\html;
use function Pure\Tags\HTML\meta;
use function Pure\Tags\HTML\title;
use function Pure\Tags\HTML\header;
use function Pure\Tags\HTML\input;
use function Pure\Tags\HTML\label;
use function Pure\Tags\HTML\li;
use function Pure\Tags\HTML\main;
use function Pure\Tags\HTML\nav;
use function Pure\Tags\HTML\p;
use function Pure\Tags\HTML\section;
use function Pure\Tags\HTML\textarea;
use function Pure\Tags\HTML\ul;

class HTMLTest extends TestCase
{
    private HTML $html;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->html = $this->createInstance();
    }

    public function createInstance()
    {
        return (
            html(
                head(
                    meta()->charset('UTF-8'),
                    title('Complex HTML Code Example')
                ),
                body(
                    div(
                        header(
                            h1('Welcome to My Website'),
                            nav(
                                ul(
                                    li(
                                        a('Home')->href('#')
                                    ),
                                    li(
                                        a('About')->href('#')
                                    ),
                                    li(
                                        a('Services')->href('#')
                                    ),
                                    rawHtml('<li><a href="#">Contact</a></li>')
                                )
                            )->class('nav')
                        )->class('header'),
                        main(
                            section(
                                h2('About Us'),
                                p('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ultrices urna eget sapien ullamcorper, vel efficitur massa semper.'),
                                a('Learn More')->href('#')->class('button')
                            )->class('section'),
                            section(
                                h2('Our Services'),
                                ul(
                                    li('Service 1'),
                                    li('Service 2'),
                                    li('Service 3')
                                )
                            )->class('section'),
                            section(
                                h2('Contact Us'),
                                form(
                                    label('Name:')->class('form-label')->for('name'),
                                    input()->type('text')->id('name')->name('name')->class('form-input'),
                                    label('Email:')->class('form-label')->for('email'),
                                    input()->type('email')->id('email')->name('email')->class('form-input'),
                                    label('Message:')->class('form-label')->for('message'),
                                    textarea()->id('message')->name('message')->class('form-input'),
                                    button('Submit')->type('submit')->class('button')
                                )
                            )->class('section')
                        ),
                        footer(
                            p('&copy; 2023 My Website. All rights reserved.')
                        )->class('footer')
                    )->class('container'),
                )
            )->lang('en')
        );
    }

    public function testTagName()
    {
        /** @var HTML */
        $tag = HTML::div();

        $this->assertSame('div', $tag->getTagName());
    }

    public function testAttributes()
    {
        /** @var HTML */
        $tag = HTML::input()
            ->type('text')
            ->id('my-input')
            ->value(0)
            ->disabled(false)
            ->readonly(null)
            ->required(true);
        $attributes = $tag->getAttributes();

        $this->assertCount(4, $attributes);
        $this->assertArrayNotHasKey('disabled', $attributes);
        $this->assertArrayNotHasKey('readonly', $attributes);
        $this->assertSame('text', $tag->getAttribute('type'));
        $this->assertSame('my-input', $tag->getAttribute('id'));
        $this->assertSame('0', $tag->getAttribute('value'));
        $this->assertSame('required', $tag->getAttribute('required'));
    }

    public function testChildren()
    {
        $child1 = 'Hello';

        /** @var HTML */
        $child2 = HTML::span('World')->style('color: red;');

        /** @var HTML */
        $tag = HTML::div($child1, $child2);

        $children = $tag->getChildren();
        $this->assertCount(2, $children);
        $this->assertSame($child1, $children[0]);
        $this->assertSame($child2, $children[1]);
    }

    public function testToJSON()
    {
        $expected = [
            'tagName' => 'html',
            'children' => [
                [
                    'tagName' => 'head',
                    'children' => [
                        [
                            'tagName' => 'meta',
                            'children' => [],
                            'charset' => 'UTF-8'
                        ],
                        [
                            'tagName' => 'title',
                            'children' => [
                                'Complex HTML Code Example'
                            ]
                        ]
                    ]
                ],
                [
                    'tagName' => 'body',
                    'children' => [
                        [
                            'tagName' => 'div',
                            'children' => [
                                [
                                    'tagName' => 'header',
                                    'children' => [
                                        [
                                            'tagName' => 'h1',
                                            'children' => [
                                                'Welcome to My Website'
                                            ]
                                        ],
                                        [
                                            'tagName' => 'nav',
                                            'children' => [
                                                [
                                                    'tagName' => 'ul',
                                                    'children' => [
                                                        [
                                                            'tagName' => 'li',
                                                            'children' => [
                                                                [
                                                                    'tagName' => 'a',
                                                                    'children' => [
                                                                        'Home'
                                                                    ],
                                                                    'href' => '#'
                                                                ]
                                                            ]
                                                        ],
                                                        [
                                                            'tagName' => 'li',
                                                            'children' => [
                                                                [
                                                                    'tagName' => 'a',
                                                                    'children' => [
                                                                        'About'
                                                                    ],
                                                                    'href' => '#'
                                                                ]
                                                            ]
                                                        ],
                                                        [
                                                            'tagName' => 'li',
                                                            'children' => [
                                                                [
                                                                    'tagName' => 'a',
                                                                    'children' => [
                                                                        'Services'
                                                                    ],
                                                                    'href' => '#'
                                                                ]
                                                            ]
                                                        ],
                                                        [
                                                            'type' => 'HTML',
                                                            'content' => '<li><a href="#">Contact</a></li>'
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            'class' => 'nav'
                                        ]
                                    ],
                                    'class' => 'header'
                                ],
                                [
                                    'tagName' => 'main',
                                    'children' => [
                                        [
                                            'tagName' => 'section',
                                            'children' => [
                                                [
                                                    'tagName' => 'h2',
                                                    'children' => [
                                                        'About Us'
                                                    ]
                                                ],
                                                [
                                                    'tagName' => 'p',
                                                    'children' => [
                                                        'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ultrices urna eget sapien ullamcorper, vel efficitur massa semper.'
                                                    ]
                                                ],
                                                [
                                                    'tagName' => 'a',
                                                    'children' => [
                                                        'Learn More'
                                                    ],
                                                    'href' => '#',
                                                    'class' => 'button'
                                                ]
                                            ],
                                            'class' => 'section'
                                        ],
                                        [
                                            'tagName' => 'section',
                                            'children' => [
                                                [
                                                    'tagName' => 'h2',
                                                    'children' => [
                                                        'Our Services'
                                                    ]
                                                ],
                                                [
                                                    'tagName' => 'ul',
                                                    'children' => [
                                                        [
                                                            'tagName' => 'li',
                                                            'children' => [
                                                                'Service 1'
                                                            ]
                                                        ],
                                                        [
                                                            'tagName' => 'li',
                                                            'children' => [
                                                                'Service 2'
                                                            ]
                                                        ],
                                                        [
                                                            'tagName' => 'li',
                                                            'children' => [
                                                                'Service 3'
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            'class' => 'section'
                                        ],
                                        [
                                            'tagName' => 'section',
                                            'children' => [
                                                [
                                                    'tagName' => 'h2',
                                                    'children' => [
                                                        'Contact Us'
                                                    ]
                                                ],
                                                [
                                                    'tagName' => 'form',
                                                    'children' => [
                                                        [
                                                            'tagName' => 'label',
                                                            'children' => [
                                                                'Name:'
                                                            ],
                                                            'class' => 'form-label',
                                                            'for' => 'name'
                                                        ],
                                                        [
                                                            'tagName' => 'input',
                                                            'children' => [],
                                                            'type' => 'text',
                                                            'id' => 'name',
                                                            'name' => 'name',
                                                            'class' => 'form-input'
                                                        ],
                                                        [
                                                            'tagName' => 'label',
                                                            'children' => [
                                                                'Email:'
                                                            ],
                                                            'class' => 'form-label',
                                                            'for' => 'email'
                                                        ],
                                                        [
                                                            'tagName' => 'input',
                                                            'children' => [],
                                                            'type' => 'email',
                                                            'id' => 'email',
                                                            'name' => 'email',
                                                            'class' => 'form-input'
                                                        ],
                                                        [
                                                            'tagName' => 'label',
                                                            'children' => [
                                                                'Message:'
                                                            ],
                                                            'class' => 'form-label',
                                                            'for' => 'message'
                                                        ],
                                                        [
                                                            'tagName' => 'textarea',
                                                            'children' => [],
                                                            'id' => 'message',
                                                            'name' => 'message',
                                                            'class' => 'form-input'
                                                        ],
                                                        [
                                                            'tagName' => 'button',
                                                            'children' => [
                                                                'Submit'
                                                            ],
                                                            'type' => 'submit',
                                                            'class' => 'button'
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            'class' => 'section'
                                        ]
                                    ],
                                ],
                                [
                                    'tagName' => 'footer',
                                    'children' => [
                                        [
                                            'tagName' => 'p',
                                            'children' => [
                                                '&copy; 2023 My Website. All rights reserved.'
                                            ]
                                        ]
                                    ],
                                    'class' => 'footer'
                                ]
                            ],
                            'class' => 'container'
                        ]
                    ]
                ]
            ],
            'lang' => 'en'
        ];

        $this->assertSame($expected, $this->html->toJSON());
    }

    public function testToString()
    {
        $expectedStr = '<html lang="en"><head><meta charset="UTF-8" /><title>Complex HTML Code Example</title></head><body><div class="container"><header class="header"><h1>Welcome to My Website</h1><nav class="nav"><ul><li><a href="#">Home</a></li><li><a href="#">About</a></li><li><a href="#">Services</a></li><li><a href="#">Contact</a></li></ul></nav></header><main><section class="section"><h2>About Us</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ultrices urna eget sapien ullamcorper, vel efficitur massa semper.</p><a href="#" class="button">Learn More</a></section><section class="section"><h2>Our Services</h2><ul><li>Service 1</li><li>Service 2</li><li>Service 3</li></ul></section><section class="section"><h2>Contact Us</h2><form><label class="form-label" for="name">Name:</label><input type="text" id="name" name="name" class="form-input" /><label class="form-label" for="email">Email:</label><input type="email" id="email" name="email" class="form-input" /><label class="form-label" for="message">Message:</label><textarea id="message" name="message" class="form-input"></textarea><button type="submit" class="button">Submit</button></form></section></main><footer class="footer"><p>&copy; 2023 My Website. All rights reserved.</p></footer></div></body></html>';
        $this->assertSame($expectedStr, (string)$this->html);
    }

    public function testSave()
    {
        $outputPath = './output.html';
        $tag = HTML::div('Hello, World!');

        $result = $tag->toSave($outputPath);
        $this->assertNotFalse($result);
        $this->assertFileExists($outputPath);

        $savedContent = file_get_contents($outputPath);
        $this->assertSame('<!DOCTYPE html><div>Hello, World!</div>', $savedContent);

        unlink($outputPath);
    }
}
