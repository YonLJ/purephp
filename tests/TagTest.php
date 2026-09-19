<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\HTML;
use Pure\Core\Raw;
use Pure\Core\Slot;
use Pure\Core\SVG;
use Pure\Core\XML;

use function Pure\HTML\button;
use function Pure\HTML\div;
use function Pure\HTML\p;
use function Pure\HTML\span;

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

    public function testRootMutationIsReflectedOnRender(): void
    {
        $tag = div(p('child'));

        $this->assertSame('<div><p>child</p></div>', $tag->render());

        $tag->id('main');

        $this->assertSame('<div id="main"><p>child</p></div>', $tag->render());
    }

    public function testDescendantMutationIsReflectedOnRender(): void
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

    public function testDeeplyNestedMutationIsReflectedOnRender(): void
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
            $this->fail('Expected LogicException to be thrown.');
        } catch (LogicException $e) {
            $this->assertSame(
                "Self-closing element 'div' cannot have child elements.",
                $e->getMessage()
            );
        }

        $this->assertFalse($tag->getSelfClose());
        $this->assertSame('<div>child</div>', $tag->render());
    }

    public function testGetAttrReturnsNullForMissingAttribute(): void
    {
        $tag = div('x')->class('container');

        $this->assertNull($tag->getAttr('missing'));
        $this->assertSame('container', $tag->getAttr('class'));
        $this->assertSame('container', $tag->getAttr('className'));
    }

    public function testSetAttrByCbReceivesNullForMissingAttribute(): void
    {
        $seen = 'unset';
        $tag = div('x');

        $tag->setAttrByCb('title', static function (mixed $value) use (&$seen): string {
            $seen = $value ?? 'null';

            return 'Created';
        });

        $this->assertSame('null', $seen);
        $this->assertSame('Created', $tag->getAttr('title'));
    }

    public function testSetAttrByCbNullRemovesAnExistingAttribute(): void
    {
        $tag = div('x')->id('main');

        $tag->setAttrByCb('id', static fn (mixed $value): ?string => null);

        $this->assertNull($tag->getAttr('id'));
        $this->assertSame('<div>x</div>', $tag->render());
    }

    public function testToJsonKeepsStructuralKeysSeparateFromAttributes(): void
    {
        $json = div('x')->tagName('attr-value')->toJSON();

        $this->assertSame('div', $json['tagName']);
        $this->assertSame(['tagName' => 'attr-value'], $json['attrs']);
        $this->assertSame(['x'], $json['children']);
    }

    public function testClassAcceptsBooleanConditions(): void
    {
        // Deliberately not a literal so static analysis cannot narrow the branch.
        $flag = getenv('PUREPHP_TEST_FLAG') !== false;

        $this->assertSame('<button class="btn">x</button>', button('x')->class('btn', $flag ? 'active' : false)->render());
        $this->assertSame('<button class="btn active">x</button>', button('x')->class('btn', 'active')->render());
    }

    public function testEmptyClassArgumentsAreDropped(): void
    {
        $this->assertSame('<div></div>', div()->class('')->render());
        $this->assertSame('<div></div>', div()->class(null)->render());
        $this->assertSame('<div></div>', div()->class([])->render());
        $this->assertSame('<div></div>', div()->class([''])->render());
        $this->assertSame('<div class="x"></div>', div()->class('', 'x')->render());
    }

    public function testClassNameAliasIsNormalizedOnSetAndGet(): void
    {
        $tag = div()->setAttrs(['className' => 'x']);

        $this->assertSame('<div class="x"></div>', $tag->render());
        $this->assertSame('x', $tag->getAttr('className'));

        $this->assertSame(
            '<div class="y"></div>',
            div()->class('x')->setAttrByCb('className', static fn (mixed $value): string => 'y')->render()
        );
    }

    public function testUnderscoredAttributeKeysRoundTripLikeMethodCalls(): void
    {
        $tag = div()->data_id('123');

        $this->assertSame('123', $tag->getAttr('data_id'));
        $this->assertSame('123', $tag->getAttr('data-id'));
        $this->assertSame('<div data-id="9"></div>', $tag->setAttrByCb('data_id', static fn (mixed $value): string => '9')->render());
    }

    public function testNonStringableAttributeValueIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("attribute 'data-config' must be a scalar, Stringable, Slot or null");

        div()->setAttrs(['data-config' => ['a' => 1]]);
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
        $tag = div(Raw::of('<b>bold</b> & raw'));

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

    public function testCallRejectsWrongArgumentCount(): void
    {
        try {
            div()->id();
            $this->fail('Expected BadMethodCallException to be thrown.');
        } catch (BadMethodCallException $e) {
            $this->assertSame("'id()' accepts exactly one parameter, 0 given.", $e->getMessage());
        }

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("'id()' accepts exactly one parameter, 2 given.");

        div()->id('a', 'b');
    }

    public function testSetAttrRejectsNumericAndEmptyNames(): void
    {
        try {
            div()->setAttrs([0 => 'x']); // @phpstan-ignore argument.type (an int-keyed map is the case under test)
            $this->fail('Expected InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Element 'div' attribute name cannot be numbers '0'.", $e->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Element 'div' attribute name cannot be empty ''.");

        div()->setAttrs(['' => 'x']);
    }

    public function testClassRejectsMixedSlotArguments(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Slot values cannot be combined with other 'class' arguments.");

        div()->class('btn', Slot::value('c'));
    }

    public function testStyleAcceptsASlot(): void
    {
        $slot = Slot::value('s');
        $tag = div()->style($slot);

        $this->assertSame($slot, $tag->getAttr('style'));
    }

    public function testPrintOutputsRenderedHTML(): void
    {
        ob_start();

        div('x')->print();

        $this->assertSame('<div>x</div>', ob_get_clean());
    }

    public function testSavePrependsTheCustomHeader(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'purephp-header-');
        $this->assertIsString($path);

        try {
            $this->assertNotFalse(div('hi')->save($path, '# H'));
            $this->assertSame("# H<div>hi</div>", file_get_contents($path));
        } finally {
            unlink($path);
        }
    }

    public function testDocumentHeaderMatchesSubclassDefaults(): void
    {
        $this->assertSame('<!DOCTYPE html>', HTML::div()->documentHeader());
        $this->assertSame('<?xml version="1.0"?>', (new XML('root'))->documentHeader());
        $this->assertSame('<?xml version="1.0"?>', (new SVG('root'))->documentHeader());
    }

    public function testVoidElementsAreCaseInsensitive(): void
    {
        $this->assertTrue((new HTML('BR'))->getSelfClose());
        $this->assertTrue((new HTML('IMG'))->getSelfClose());
        $this->assertFalse((new HTML('div'))->getSelfClose());
        $this->assertFalse((new HTML('DIV'))->getSelfClose());
        $this->assertSame('<img />', (string)(new HTML('img')));
    }
}
