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

    public function testCamelCaseSelfClosingTagsAreRecognized(): void
    {
        foreach ([
            'animateMotion', 'animateTransform', 'feBlend', 'feColorMatrix',
            'feComposite', 'feConvolveMatrix', 'feDistantLight', 'feDisplacementMap',
            'feDropShadow', 'feFlood', 'feFuncR', 'feGaussianBlur', 'feImage',
            'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpotLight',
            'feTile', 'feTurbulence',
        ] as $name) {
            $this->assertTrue((new SVG($name))->getSelfClose(), "SVG '{$name}' should be self-closing.");
        }

        // Element names are case-sensitive: a name outside the list stays a
        // container, and an unknown casing is not folded into the list.
        $this->assertFalse((new SVG('feComponentTransfer'))->getSelfClose());
        $this->assertFalse((new SVG('feMerge'))->getSelfClose());
        $this->assertFalse((new SVG('FEBLEND'))->getSelfClose());
    }

    public function testLeafElementsRenderSelfClosed(): void
    {
        $this->assertSame('<feTile />', (string)(new SVG('feTile')));
        $this->assertSame('<animateTransform />', (string)(new SVG('animateTransform')));
        $this->assertSame('<set />', (string)(new SVG('set')));
        $this->assertSame('<view />', (string)(new SVG('view')));
    }

    public function testChildrenKeepElementsOpen(): void
    {
        // SVG has no void elements: animate and animateMotion may nest mpath
        // (SMIL motion along a path), and use may nest descriptive elements.
        $this->assertSame(
            '<animateMotion><mpath href="#p" /></animateMotion>',
            (string)SVG::animateMotion(SVG::mpath()->href('#p'))
        );
        $this->assertSame(
            '<animate><mpath href="#p" /></animate>',
            (string)SVG::animate(SVG::mpath()->href('#p'))
        );
        $this->assertSame(
            '<use><title>label</title></use>',
            (string)SVG::use(SVG::title('label'))
        );
        $this->assertTrue(SVG::animateMotion()->getSelfClose());
        $this->assertTrue(SVG::use()->getSelfClose());
    }

    public function testCamelCaseSelfClosingTagOutput(): void
    {
        $this->assertSame(
            '<feBlend in="SourceGraphic" />',
            (string)(new SVG('feBlend'))->in('SourceGraphic')
        );
    }

    public function testAttributeValuesAreEscaped(): void
    {
        $circle = SVG::circle()
            ->cx('50')
            ->cy('50')
            ->r('40')
            ->fill('" onmouseover="alert(1)');

        $svg = SVG::svg($circle)->width('100')->height('100');

        $this->assertSame(
            '<svg width="100" height="100"><circle cx="50" cy="50" r="40" fill="&quot; onmouseover=&quot;alert(1)" /></svg>',
            (string)$svg
        );
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
