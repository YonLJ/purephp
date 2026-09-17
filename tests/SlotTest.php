<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\div;
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
            Slot::if('admin', Compile::shape(span('admin')), Compile::shape(span('guest')))
        ));

        $this->assertSame('<div><span>admin</span></div>', $shape(['admin' => true]));
        $this->assertSame('<div><span>guest</span></div>', $shape(['admin' => false]));
        $this->assertSame('<div><span>guest</span></div>', $shape([]));
    }

    public function testIfWithoutElseRendersNothingWhenFalsy(): void
    {
        $shape = Compile::shape(div('a', Slot::if('show', Compile::shape(span('!'))), 'b'));

        $this->assertSame('<div>ab</div>', $shape([]));
        $this->assertSame('<div>a<span>!</span>b</div>', $shape(['show' => 1]));
    }

    public function testIfBranchesShareTheCurrentScope(): void
    {
        $shape = Compile::shape(div(
            Slot::if('admin', Compile::shape(span(Slot::text('name'))))
        ));

        $this->assertSame('<div><span>Tom</span></div>', $shape(['admin' => true, 'name' => 'Tom']));
    }

    public function testIfInsideEachUsesItemScope(): void
    {
        $item = Compile::shape(li(Slot::text('name'), Slot::if('admin', Compile::shape(span('(a)')))));
        $shape = Compile::shape(ul(Slot::each('items', $item)));

        $this->assertSame(
            '<ul><li>Tom<span>(a)</span></li><li>Ann</li></ul>',
            $shape(['items' => [['name' => 'Tom', 'admin' => true], ['name' => 'Ann']]])
        );
    }

    public function testIfModifiersAreRejected(): void
    {
        $slot = Slot::if('show', Compile::shape(span('x')));

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
                Slot::text('value')->default($bad);
                $this->fail('Expected InvalidArgumentException to be thrown.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('default must be null, a scalar or an array of value types', $e->getMessage());
            }
        }
    }

    public function testDefaultAcceptsAValueTypeArray(): void
    {
        $item = Compile::shape(li(Slot::text('v')));
        $shape = Compile::shape(ul(Slot::each('items', $item)->default([])));

        $this->assertSame('<ul></ul>', $shape([]));
    }

    public function testEachAnyDispatchesByKind(): void
    {
        $shape = Compile::shape(div(Slot::eachAny('items', [
            'text' => Compile::shape(p(Slot::text('value'))),
            'link' => Compile::shape(a(Slot::text('value'))->href(Slot::attr('href'))),
        ])));

        $this->assertSame(
            '<div><p>hi</p><a href="#x">go</a></div>',
            $shape(['items' => [
                ['kind' => 'text', 'value' => 'hi'],
                ['kind' => 'link', 'value' => 'go', 'href' => '#x'],
            ]])
        );
    }

    public function testEachAnySupportsACustomKindKey(): void
    {
        $shape = Compile::shape(div(Slot::eachAny('items', [
            'a' => Compile::shape(span('A')),
        ], 'type')));

        $this->assertSame('<div><span>A</span></div>', $shape(['items' => [['type' => 'a']]]));
    }

    public function testEachAnyAppliesTheMapPerItem(): void
    {
        $shape = Compile::shape(div(Slot::eachAny('items', [
            'x' => Compile::shape(span(Slot::text('v'))),
        ], 'kind', static fn (mixed $item): array => ['v' => '#' . (is_array($item) ? (string)$item['n'] : '')])));

        $this->assertSame('<div><span>#1</span></div>', $shape(['items' => [['kind' => 'x', 'n' => 1]]]));
    }

    public function testEachAnyRejectsUnknownKindWithFullPath(): void
    {
        $shape = Compile::shape(div(Slot::eachAny('items', [
            'text' => Compile::shape(p(Slot::text('value'))),
        ])));

        try {
            $shape(['items' => [['kind' => 'video', 'value' => 'x']]]);
            $this->fail('Expected InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("slot 'items[].kind'", $e->getMessage());
            $this->assertStringContainsString("'text'", $e->getMessage());
        }
    }

    public function testEachAnyRejectsNonArrayAndMissingKind(): void
    {
        $shape = Compile::shape(div(Slot::eachAny('items', [
            'text' => Compile::shape(p(Slot::text('value'))),
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

    public function testEachAnyRequiresAtLeastOneShape(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Slot::eachAny('items', []);
    }

    public function testEachAnyRejectsInvalidKindKeys(): void
    {
        try {
            Slot::eachAny('items', ['' => Compile::shape(span('x'))]);
            $this->fail('Expected InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("slot 'items' eachAny kinds must be non-empty strings.", $e->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("slot 'items' eachAny kinds must be non-empty strings.");

        Slot::eachAny('items', [0 => Compile::shape(span('x'))]);
    }

    public function testEachAnyRejectsNonStringKindValue(): void
    {
        $shape = Compile::shape(div(Slot::eachAny('items', [
            'text' => Compile::shape(p('x')),
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

        Compile::shape(div('x')->class(Slot::if('on', Compile::shape(span('y')))))->compile();
    }

    public function testEachAnySlotInAttributePositionIsRejected(): void
    {
        $this->expectException(LogicException::class);

        Compile::shape(div('x')->class(Slot::eachAny('items', [
            'a' => Compile::shape(span('y')),
        ])))->compile();
    }

    public function testDuplicateSlotPathsKeepDistinctMaps(): void
    {
        $listA = Compile::shape(div(Slot::text('tag')));
        $listB = Compile::shape(div(Slot::text('tag')));

        $shape = Compile::shape(div(
            Slot::each('items', $listA, static fn (mixed $item): array => ['tag' => 'mapA']),
            Slot::each('items', $listB, static fn (mixed $item): array => ['tag' => 'mapB'])
        ));

        $this->assertSame(
            '<div><div>mapA</div><div>mapB</div></div>',
            $shape(['items' => [['tag' => 'orig']]])
        );
    }

    public function testIfBranchesKeepDistinctMaps(): void
    {
        $card = Compile::shape(span(Slot::text('tag')));
        $then = Compile::shape(div(Slot::sub('card', $card, static fn (mixed $d): array => ['tag' => 'thenMap'])));
        $else = Compile::shape(div(Slot::sub('card', $card, static fn (mixed $d): array => ['tag' => 'elseMap'])));

        $shape = Compile::shape(div(Slot::if('flag', $then, $else)));

        $this->assertSame(
            '<div><div><span>thenMap</span></div></div>',
            $shape(['flag' => true])
        );
        $this->assertSame(
            '<div><div><span>elseMap</span></div></div>',
            $shape(['flag' => false])
        );
    }
}
