<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use function Pure\HTML\div;
use function Pure\HTML\p;
use function Pure\HTML\span;
use function Pure\Utils\rawHtml;

class TagTest extends TestCase
{
    public function testToStringIsEquivalentToRender(): void
    {
        $tag = div(p('Hello'))->class('container');

        $this->assertSame('<div class="container"><p>Hello</p></div>', $tag->render());
        $this->assertSame($tag->render(), (string)$tag);
    }

    public function testRepeatedRenderReturnsTheSameOutput(): void
    {
        $tag = div(p('child'))->id('main');

        $this->assertSame($tag->render(), $tag->render());
    }

    public function testRootMutationInvalidatesRenderCache(): void
    {
        $tag = div(p('child'));

        $this->assertSame('<div><p>child</p></div>', $tag->render());

        $tag->id('main');

        $this->assertSame('<div id="main"><p>child</p></div>', $tag->render());
    }

    public function testDescendantMutationInvalidatesRenderCache(): void
    {
        $child = p('child');
        $tag = div($child, span('static'));

        $this->assertSame('<div><p>child</p><span>static</span></div>', $tag->render());

        $child->class('updated');

        $this->assertSame(
            '<div><p class="updated">child</p><span>static</span></div>',
            $tag->render()
        );
    }

    public function testDeeplyNestedMutationInvalidatesRenderCache(): void
    {
        $leaf = span('leaf');
        $tag = div(p(div($leaf)));

        $this->assertSame('<div><p><div><span>leaf</span></div></p></div>', $tag->render());

        $leaf->title('deep');

        $this->assertSame(
            '<div><p><div><span title="deep">leaf</span></div></p></div>',
            $tag->render()
        );
    }

    public function testSharedChildIsRenderedInEveryPosition(): void
    {
        $shared = p('shared');
        $tag = div($shared, $shared);

        $this->assertSame('<div><p>shared</p><p>shared</p></div>', $tag->render());

        $shared->id('x');

        $this->assertSame('<div><p id="x">shared</p><p id="x">shared</p></div>', $tag->render());
    }

    public function testSetSelfCloseChangesRender(): void
    {
        $tag = div();

        $this->assertSame('<div></div>', $tag->render());

        $tag->setSelfClose(true);

        $this->assertSame('<div />', $tag->render());
    }

    public function testSetSelfCloseWithChildrenThrowsAndKeepsState(): void
    {
        $tag = div('child');

        try {
            $tag->setSelfClose(true);
            $this->fail('Expected ErrorException to be thrown.');
        } catch (ErrorException $e) {
            $this->assertSame(
                "Self-closing element 'div' cannot have child elements.",
                $e->getMessage()
            );
        }

        $this->assertFalse($tag->getSelfClose());
        $this->assertSame('<div>child</div>', $tag->render());
    }

    public function testTextChildrenAreEscaped(): void
    {
        $tag = div(p('Tom & Jerry < 10'));

        $this->assertSame('<div><p>Tom &amp; Jerry &lt; 10</p></div>', $tag->render());
    }

    public function testAlreadyEscapedEntitiesAreNotDoubleEncoded(): void
    {
        $tag = div(p('&copy; 2023 My Website'));

        $this->assertSame('<div><p>&copy; 2023 My Website</p></div>', $tag->render());
    }

    public function testRawChildrenAreNotEscaped(): void
    {
        $tag = div(rawHtml('<b>bold</b> & raw'));

        $this->assertSame('<div><b>bold</b> & raw</div>', $tag->render());
    }

    public function testEscapedTextIsRenderedFreshlyAfterMutation(): void
    {
        $child = p('a & b');
        $tag = div($child);

        $this->assertSame('<div><p>a &amp; b</p></div>', $tag->render());

        $child->id('x');

        $this->assertSame('<div><p id="x">a &amp; b</p></div>', $tag->render());
    }

    public function testSharedChildIsUpdatedInEveryParent(): void
    {
        $shared = p('a');
        $first = div($shared, 'A');
        $second = div($shared, 'B');

        $this->assertSame('<div><p>a</p>A</div>', $first->render());
        $this->assertSame('<div><p>a</p>B</div>', $second->render());

        $shared->class('x');

        $this->assertSame('<div><p class="x">a</p>A</div>', $first->render());
        $this->assertSame('<div><p class="x">a</p>B</div>', $second->render());
    }

    public function testRenderingAChildDoesNotFreezeParentOutput(): void
    {
        $child = p('leaf');
        $tag = div($child);

        $this->assertSame('<div><p>leaf</p></div>', $tag->render());

        $child->id('changed');

        $this->assertSame('<p id="changed">leaf</p>', $child->render());
        $this->assertSame('<div><p id="changed">leaf</p></div>', $tag->render());
    }

    public function testInvalidUtf8TextIsReplacedInsteadOfDropped(): void
    {
        $tag = div("caf\xE9");

        $this->assertSame("<div>caf\u{FFFD}</div>", $tag->render());
    }

    public function testInvalidUtf8AttributeValueIsReplacedInsteadOfDropped(): void
    {
        $tag = div('x')->title("caf\xE9");

        $this->assertSame("<div title=\"caf\u{FFFD}\">x</div>", $tag->render());
    }
}
