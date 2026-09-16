<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\HTML;

use function Pure\HTML\a;
use function Pure\HTML\body;
use function Pure\HTML\button;
use function Pure\HTML\div;
use function Pure\HTML\footer;
use function Pure\HTML\form;
use function Pure\HTML\h1;
use function Pure\HTML\h2;
use function Pure\HTML\head;
use function Pure\HTML\header;
use function Pure\HTML\html;
use function Pure\HTML\input;
use function Pure\HTML\label;
use function Pure\HTML\li;
use function Pure\HTML\main;
use function Pure\HTML\meta;
use function Pure\HTML\nav;
use function Pure\HTML\p;
use function Pure\HTML\section;
use function Pure\HTML\textarea;
use function Pure\HTML\title;
use function Pure\HTML\ul;
use function Pure\Utils\rawHtml;

class HTMLTest extends TestCase
{
    private HTML $html;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->html = $this->createInstance();
    }

    public function createInstance(): HTML
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

    public function testTagName(): void
    {
        /** @var HTML */
        $tag = HTML::div();

        $this->assertSame('div', $tag->getTagName());
    }

    public function testMagicStaticMethod(): void
    {
        /** @var HTML */
        $tag = HTML::div('Hello World');

        $this->assertSame('div', $tag->getTagName());
        $this->assertSame(['Hello World'], $tag->getChildren());
    }

    public function testConstructorMethod(): void
    {
        /** @var HTML */
        $tag = new HTML('custom-tag', ['Custom Content']);

        $this->assertSame('custom-tag', $tag->getTagName());
        $this->assertSame(['Custom Content'], $tag->getChildren());
    }

    public function testStringChildrenAreEscapedNotFiltered(): void
    {
        // String children are text: markup-looking content is escaped so no
        // data is lost. Use rawHtml() to emit trusted markup.
        $tag = HTML::div('<p>This stays visible</p>', '<strong>This too</strong>', 'a<b');

        $output = (string)$tag;

        $this->assertSame(
            '<div>&lt;p&gt;This stays visible&lt;/p&gt;&lt;strong&gt;This too&lt;/strong&gt;a&lt;b</div>',
            $output
        );
    }

    public function testRawHtmlPreservesContent(): void
    {
        // Test that rawHtml preserves HTML content
        $tag = HTML::div(
            rawHtml('<p>This should be preserved</p>'),
            rawHtml('<strong>This too</strong>')
        );

        $output = (string)$tag;

        // The HTML tags should be preserved
        $this->assertStringContainsString('<p>This should be preserved</p>', $output);
        $this->assertStringContainsString('<strong>This too</strong>', $output);
    }

    public function testClassName(): void
    {
        /** @var HTML */
        $tag = HTML::div()
            ->className(
                'class-a class-b',
                'class-c',
                [
                    'class-d',
                    'class-e' => true,
                    'class-f' => false,
                    'class-g' => null,
                    'class-h' => 0,
                    'class-i' => '',
                    'class-j' => 'not empty string',
                    null,
                    '',
                ]
            );

        $this->assertSame('class-a class-b class-c class-d class-e class-j', $tag->getAttr('class'));
    }

    public function testStyle(): void
    {
        /** @var HTML */
        $tag = HTML::div()
            ->style([
                'color' => false,
                'background-color' => '#fff',
                'line-height' => 1.5,
                'font-size' => '20px',
                'position' => null,
            ]);
        $this->assertSame('background-color: #fff; line-height: 1.5; font-size: 20px;', $tag->getAttr('style'));
    }

    public function testAttributes(): void
    {
        /** @var HTML */
        $tag = HTML::input()
            ->type('text')
            ->id('my-input')
            ->value(0)
            ->disabled(false)
            ->readonly(null)
            ->required(true)
            ->class('class-a class-b class-c')
            ->style('display: inline-block; color: #eee; margin-left: 10px;');
        $attrs = $tag->getAttrs();

        $this->assertCount(6, $attrs);
        $this->assertArrayNotHasKey('disabled', $attrs);
        $this->assertArrayNotHasKey('readonly', $attrs);
        $this->assertSame('text', $tag->getAttr('type'));
        $this->assertSame('my-input', $tag->getAttr('id'));
        $this->assertSame('0', $tag->getAttr('value'));
        $this->assertSame('required', $tag->getAttr('required'));
        $this->assertSame('class-a class-b class-c', $tag->getAttr('class'));
        $this->assertSame('display: inline-block; color: #eee; margin-left: 10px;', $tag->getAttr('style'));
    }

    public function testAttributeValuesAreEscaped(): void
    {
        /** @var HTML */
        $tag = HTML::input()
            ->type('text')
            ->value('"><script>alert(1)</script>');

        $this->assertSame(
            '<input type="text" value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;" />',
            (string)$tag
        );
    }

    public function testChildren(): void
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

    public function testToJSON(): void
    {
        $expected = self::node('html', ['lang' => 'en'], [
            self::node('head', [], [
                self::node('meta', ['charset' => 'UTF-8']),
                self::node('title', [], ['Complex HTML Code Example']),
            ]),
            self::node('body', [], [
                self::node('div', ['class' => 'container'], [
                    self::node('header', ['class' => 'header'], [
                        self::node('h1', [], ['Welcome to My Website']),
                        self::node('nav', ['class' => 'nav'], [
                            self::node('ul', [], [
                                self::node('li', [], [self::node('a', ['href' => '#'], ['Home'])]),
                                self::node('li', [], [self::node('a', ['href' => '#'], ['About'])]),
                                self::node('li', [], [self::node('a', ['href' => '#'], ['Services'])]),
                                rawHtml('<li><a href="#">Contact</a></li>')->toJSON(),
                            ]),
                        ]),
                    ]),
                    self::node('main', [], [
                        self::node('section', ['class' => 'section'], [
                            self::node('h2', [], ['About Us']),
                            self::node('p', [], ['Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ultrices urna eget sapien ullamcorper, vel efficitur massa semper.']),
                            self::node('a', ['href' => '#', 'class' => 'button'], ['Learn More']),
                        ]),
                        self::node('section', ['class' => 'section'], [
                            self::node('h2', [], ['Our Services']),
                            self::node('ul', [], [
                                self::node('li', [], ['Service 1']),
                                self::node('li', [], ['Service 2']),
                                self::node('li', [], ['Service 3']),
                            ]),
                        ]),
                        self::node('section', ['class' => 'section'], [
                            self::node('h2', [], ['Contact Us']),
                            self::node('form', [], [
                                self::node('label', ['class' => 'form-label', 'for' => 'name'], ['Name:']),
                                self::node('input', ['type' => 'text', 'id' => 'name', 'name' => 'name', 'class' => 'form-input']),
                                self::node('label', ['class' => 'form-label', 'for' => 'email'], ['Email:']),
                                self::node('input', ['type' => 'email', 'id' => 'email', 'name' => 'email', 'class' => 'form-input']),
                                self::node('label', ['class' => 'form-label', 'for' => 'message'], ['Message:']),
                                self::node('textarea', ['id' => 'message', 'name' => 'message', 'class' => 'form-input']),
                                self::node('button', ['type' => 'submit', 'class' => 'button'], ['Submit']),
                            ]),
                        ]),
                    ]),
                    self::node('footer', ['class' => 'footer'], [
                        self::node('p', [], ['&copy; 2023 My Website. All rights reserved.']),
                    ]),
                ]),
            ]),
        ]);

        $this->assertSame($expected, $this->html->toJSON());
    }

    /**
     * @param array<string, mixed> $attrs
     * @param array<int, mixed> $children
     *
     * @return array{tagName: string, attrs: array<string, mixed>, children: array<int, mixed>}
     */
    private static function node(string $tagName, array $attrs = [], array $children = []): array
    {
        return ['tagName' => $tagName, 'attrs' => $attrs, 'children' => $children];
    }

    public function testToString(): void
    {
        $expectedStr = '<html lang="en"><head><meta charset="UTF-8" /><title>Complex HTML Code Example</title></head><body><div class="container"><header class="header"><h1>Welcome to My Website</h1><nav class="nav"><ul><li><a href="#">Home</a></li><li><a href="#">About</a></li><li><a href="#">Services</a></li><li><a href="#">Contact</a></li></ul></nav></header><main><section class="section"><h2>About Us</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ultrices urna eget sapien ullamcorper, vel efficitur massa semper.</p><a href="#" class="button">Learn More</a></section><section class="section"><h2>Our Services</h2><ul><li>Service 1</li><li>Service 2</li><li>Service 3</li></ul></section><section class="section"><h2>Contact Us</h2><form><label class="form-label" for="name">Name:</label><input type="text" id="name" name="name" class="form-input" /><label class="form-label" for="email">Email:</label><input type="email" id="email" name="email" class="form-input" /><label class="form-label" for="message">Message:</label><textarea id="message" name="message" class="form-input"></textarea><button type="submit" class="button">Submit</button></form></section></main><footer class="footer"><p>&copy; 2023 My Website. All rights reserved.</p></footer></div></body></html>';
        $this->assertSame($expectedStr, (string)$this->html);
    }

    public function testSave(): void
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
