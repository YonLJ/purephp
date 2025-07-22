<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\SVG;

class SVGTest extends TestCase
{
    public function testMagicStaticMethod(): void
    {
        // Test magic static method approach
        $svg = SVG::svg(
            SVG::circle()->cx('50')->cy('50')->r('40')->fill('red'),
            SVG::rect()->x('10')->y('10')->width('80')->height('80')->fill('blue')
        )->width('100')->height('100');

        $this->assertSame('svg', $svg->getTagName());
        $this->assertCount(2, $svg->getChildren());
        $this->assertSame('100', $svg->getAttr('width'));
        $this->assertSame('100', $svg->getAttr('height'));
    }

    public function testConstructorMethod(): void
    {
        // Test constructor approach
        $circle = (new SVG('circle'))
            ->cx('50')
            ->cy('50')
            ->r('40')
            ->fill('red');

        $rect = (new SVG('rect'))
            ->x('10')
            ->y('10')
            ->width('80')
            ->height('80')
            ->fill('blue');

        $svg = (new SVG('svg', [$circle, $rect]))
            ->width('100')
            ->height('100');

        $this->assertSame('svg', $svg->getTagName());
        $this->assertCount(2, $svg->getChildren());
        $this->assertSame('100', $svg->getAttr('width'));
        $this->assertSame('100', $svg->getAttr('height'));
    }

    public function testCustomSVGTags(): void
    {
        // Test creating custom SVG tags using constructor
        $customTag = new SVG('custom-element', ['Custom SVG Content']);

        $this->assertSame('custom-element', $customTag->getTagName());
        $this->assertSame(['Custom SVG Content'], $customTag->getChildren());
    }

    public function testSelfClosingTags(): void
    {
        // Test that self-closing SVG tags are properly handled
        $circle = SVG::circle();
        $rect = new SVG('rect');

        $this->assertTrue($circle->getSelfClose());
        $this->assertTrue($rect->getSelfClose());
    }

    public function testSVGOutput(): void
    {
        $svg = SVG::svg(
            SVG::circle()->cx('25')->cy('25')->r('20')->fill('red')
        )->width('50')->height('50');

        $expected = '<svg width="50" height="50"><circle cx="25" cy="25" r="20" fill="red" /></svg>';
        $this->assertSame($expected, (string)$svg);
    }
}
