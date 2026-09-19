<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\em;
use function Pure\HTML\li;
use function Pure\HTML\span;
use function Pure\HTML\ul;

/**
 * Behaviour of the conditional and list slots.
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
        ));

        $wrapped = Compile::shape(div(
            Slot::child('box', Compile::shape(span(Slot::value('label')))),
            Slot::each('items', Compile::shape(li(Slot::value('value')))),
            Slot::if('flag', Compile::shape(em('on')), Compile::shape(em('off'))),
        ));

        $data = [
            'box' => ['label' => 'boxed'],
            'items' => [['value' => 'one'], ['value' => 'two']],
            'flag' => true,
        ];

        $expected = '<div><span>boxed</span><li>one</li><li>two</li><em>on</em></div>';

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

    public function testIfSlotInAttributePositionIsRejected(): void
    {
        $this->expectException(LogicException::class);

        Compile::shape(div('x')->class(Slot::if('on', span('y'))))->compile();
    }
}
