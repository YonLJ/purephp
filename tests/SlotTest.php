<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\b;
use function Pure\HTML\div;
use function Pure\HTML\em;
use function Pure\HTML\i;
use function Pure\HTML\li;
use function Pure\HTML\p;
use function Pure\HTML\span;
use function Pure\HTML\ul;

/**
 * Behaviour of the conditional and heterogeneous slots.
 */
class SlotTest extends TestCase
{
    public function testIfRendersThenOrElseBranch(): void
    {
        $shape = Compile::shape(div(
            Slot::if('admin', span('admin'), span('guest'))
        ));

        $this->assertSame('<div><span>admin</span></div>', $shape(['admin' => true]));
        $this->assertSame('<div><span>guest</span></div>', $shape(['admin' => false]));
        $this->assertSame('<div><span>guest</span></div>', $shape([]));
    }

    public function testIfWithoutElseRendersNothingWhenFalsy(): void
    {
        $shape = Compile::shape(div('a', Slot::if('show', span('!')), 'b'));

        $this->assertSame('<div>ab</div>', $shape([]));
        $this->assertSame('<div>a<span>!</span>b</div>', $shape(['show' => 1]));
    }

    public function testIfBranchesShareTheCurrentScope(): void
    {
        $shape = Compile::shape(div(
            Slot::if('admin', span(Slot::value('name')))
        ));

        $this->assertSame('<div><span>Tom</span></div>', $shape(['admin' => true, 'name' => 'Tom']));
    }

    public function testIfInsideEachUsesItemScope(): void
    {
        $item = Compile::shape(li(Slot::value('name'), Slot::if('admin', span('(a)'))));
        $shape = Compile::shape(ul(Slot::each('items', $item)));

        $this->assertSame(
            '<ul><li>Tom<span>(a)</span></li><li>Ann</li></ul>',
            $shape(['items' => [['name' => 'Tom', 'admin' => true], ['name' => 'Ann']]])
        );
    }

    public function testNestedSlotsAcceptBareTagTrees(): void
    {
        $bare = Compile::shape(div(
            Slot::child('box', span(Slot::value('label'))),
            Slot::each('items', li(Slot::value('value'))),
            Slot::if('flag', em('on'), em('off')),
            Slot::eachKind('kinds', ['a' => i('A'), 'b' => b('B')]),
        ));

        $wrapped = Compile::shape(div(
            Slot::child('box', Compile::shape(span(Slot::value('label')))),
            Slot::each('items', Compile::shape(li(Slot::value('value')))),
            Slot::if('flag', Compile::shape(em('on')), Compile::shape(em('off'))),
            Slot::eachKind('kinds', ['a' => Compile::shape(i('A')), 'b' => Compile::shape(b('B'))]),
        ));

        $data = [
            'box' => ['label' => 'boxed'],
            'items' => [['value' => 'one'], ['value' => 'two']],
            'flag' => true,
            'kinds' => [['kind' => 'a'], ['kind' => 'b']],
        ];

        $expected = '<div><span>boxed</span><li>one</li><li>two</li><em>on</em><i>A</i><b>B</b></div>';

        $this->assertSame($expected, $bare($data));
        $this->assertSame($expected, $wrapped($data));
        $this->assertSame($bare->id(), $wrapped->id());
    }

    public function testIfModifiersAreRejected(): void
    {
        $slot = Slot::if('show', span('x'));

        try {
            $slot->default(true);
            $this->fail('Expected LogicException to be thrown.');
        } catch (LogicException $e) {
            $this->assertStringContainsString("slot 'show' is a condition slot", $e->getMessage());
        }

        $this->expectException(LogicException::class);

        $slot->required(false);
    }

    public function testDefaultRejectsNonValueTypes(): void
    {
        foreach ([static fn (): string => 'x', ['nested' => new stdClass()], new stdClass()] as $bad) {
            try {
                Slot::value('value')->default($bad);
                $this->fail('Expected InvalidArgumentException to be thrown.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('default must be null, a scalar or an array of value types', $e->getMessage());
            }
        }
    }

    public function testDefaultAcceptsAValueTypeArray(): void
    {
        $item = Compile::shape(li(Slot::value('v')));
        $shape = Compile::shape(ul(Slot::each('items', $item)->default([])));

        $this->assertSame('<ul></ul>', $shape([]));
    }

    public function testEachKindDispatchesByKind(): void
    {
        $shape = Compile::shape(div(Slot::eachKind('items', [
            'text' => Compile::shape(p(Slot::value('value'))),
            'link' => Compile::shape(a(Slot::value('value'))->href(Slot::value('href'))),
        ])));

        $this->assertSame(
            '<div><p>hi</p><a href="#x">go</a></div>',
            $shape(['items' => [
                ['kind' => 'text', 'value' => 'hi'],
                ['kind' => 'link', 'value' => 'go', 'href' => '#x'],
            ]])
        );
    }

    public function testEachKindSupportsACustomKindKey(): void
    {
        $shape = Compile::shape(div(Slot::eachKind('items', [
            'a' => Compile::shape(span('A')),
        ], 'type')));

        $this->assertSame('<div><span>A</span></div>', $shape(['items' => [['type' => 'a']]]));
    }

    public function testEachKindRejectsUnknownKindWithFullPath(): void
    {
        $shape = Compile::shape(div(Slot::eachKind('items', [
            'text' => Compile::shape(p(Slot::value('value'))),
        ])));

        try {
            $shape(['items' => [['kind' => 'video', 'value' => 'x']]]);
            $this->fail('Expected InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("slot 'items[].kind'", $e->getMessage());
            $this->assertStringContainsString("'text'", $e->getMessage());
        }
    }

    public function testEachKindRejectsNonArrayAndMissingKind(): void
    {
        $shape = Compile::shape(div(Slot::eachKind('items', [
            'text' => Compile::shape(p(Slot::value('value'))),
        ])));

        try {
            $shape(['items' => ['nope']]);
            $this->fail('Expected InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("slot 'items[]' must be an array, string given.", $e->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("slot 'items[].kind' is required but was not provided.");

        $shape(['items' => [[]]]);
    }

    public function testEachKindRequiresAtLeastOneShape(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Slot::eachKind('items', []);
    }

    public function testEachKindRejectsInvalidKindKeys(): void
    {
        try {
            Slot::eachKind('items', ['' => span('x')]);
            $this->fail('Expected InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("slot 'items' eachKind variants must be non-empty strings.", $e->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("slot 'items' eachKind variants must be non-empty strings.");

        Slot::eachKind('items', [0 => span('x')]);
    }

    public function testEachKindRejectsNonStringKindValue(): void
    {
        $shape = Compile::shape(div(Slot::eachKind('items', [
            'text' => p('x'),
        ])));

        try {
            $shape(['items' => [['kind' => 42]]]);
            $this->fail('Expected InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("slot 'items[].kind' must be one of 'text', int given.", $e->getMessage());
        }
    }

    public function testIfSlotInAttributePositionIsRejected(): void
    {
        $this->expectException(LogicException::class);

        Compile::shape(div('x')->class(Slot::if('on', span('y'))))->compile();
    }

    public function testEachKindSlotInAttributePositionIsRejected(): void
    {
        $this->expectException(LogicException::class);

        Compile::shape(div('x')->class(Slot::eachKind('items', [
            'a' => Compile::shape(span('y')),
        ])))->compile();
    }

}
