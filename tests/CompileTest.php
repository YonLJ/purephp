<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Core\HTML;
use Pure\Core\MissingSlotException;
use Pure\Core\Slot;
use Pure\Core\XML;

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\li;
use function Pure\HTML\p;
use function Pure\HTML\span;
use function Pure\HTML\table;
use function Pure\HTML\td;
use function Pure\HTML\tr;
use function Pure\HTML\ul;
use function Pure\Utils\rawHtml;

class CompileTest extends TestCase
{
    public function testStaticShapeMatchesRenderByteForByte(): void
    {
        $tree = div(p('a & b < 10'), span('&copy; 2023'))->class('x')->id('i');

        $this->assertSame($tree->render(), Compile::shape($tree)([]));
    }

    public function testStaticVoidAndSelfClosingTagsMatchRender(): void
    {
        $this->assertSame('<br />', Compile::shape(HTML::br())([]));
        $this->assertSame(
            XML::item(XML::title('t & t'))->render(),
            Compile::shape(XML::item(XML::title('t & t')))([])
        );
    }

    public function testRawChildrenStayVerbatim(): void
    {
        $tree = div(rawHtml('<b>bold</b> & raw'), 'plain & text');

        $this->assertSame($tree->render(), Compile::shape($tree)([]));
    }

    public function testTextAndAttributeSlotsAreEscaped(): void
    {
        $shape = Compile::shape(
            h1(Slot::text('title'))->class(Slot::attr('cls'))
        );

        $this->assertSame(
            '<h1 class="a &quot;b&quot; &amp; c">Tom &amp; Jerry &lt; 10</h1>',
            $shape(['title' => 'Tom & Jerry < 10', 'cls' => 'a "b" & c'])
        );
    }

    public function testRawSlotIsNotEscaped(): void
    {
        $shape = Compile::shape(div(Slot::raw('body')));

        $this->assertSame('<div><i>raw</i> & more</div>', $shape(['body' => '<i>raw</i> & more']));
    }

    public function testNullTextSlotRendersEmptyContent(): void
    {
        $shape = Compile::shape(div(Slot::text('value')));

        $this->assertSame('<div></div>', $shape(['value' => null]));
    }

    public function testNullAttributeSlotOmitsTheAttribute(): void
    {
        $shape = Compile::shape(div('x')->class(Slot::attr('cls')));

        $this->assertSame('<div>x</div>', $shape(['cls' => null]));
        $this->assertSame('<div class="a">x</div>', $shape(['cls' => 'a']));
    }

    public function testRequiredSlotThrowsWithFullPath(): void
    {
        $item = Compile::shape(li(Slot::text('title')));
        $shape = Compile::shape(ul(Slot::each('items', $item)));

        try {
            $shape(['items' => [[]]]);
            $this->fail('Expected MissingSlotException to be thrown.');
        } catch (MissingSlotException $e) {
            $this->assertSame("slot 'items[].title' is required but was not provided.", $e->getMessage());
        }
    }

    public function testOptionalSlotFallsBackToDefault(): void
    {
        $shape = Compile::shape(div(Slot::text('maybe')->default('fallback')));

        $this->assertSame('<div>fallback</div>', $shape([]));
        $this->assertSame('<div>set</div>', $shape(['maybe' => 'set']));
    }

    public function testEachSlotRendersEveryItem(): void
    {
        $item = Compile::shape(li(Slot::text('title')));
        $shape = Compile::shape(ul(Slot::each('items', $item))->class('list'));

        $this->assertSame(
            '<ul class="list"><li>a &amp; b</li><li>c</li></ul>',
            $shape(['items' => [['title' => 'a & b'], ['title' => 'c']]])
        );
    }

    public function testSubSlotUsesNestedDataScope(): void
    {
        $card = Compile::shape(div(span(Slot::text('name')))->class('card'));
        $shape = Compile::shape(div(Slot::sub('card', $card), Slot::text('after')));

        $this->assertSame(
            '<div><div class="card"><span>n</span></div>!</div>',
            $shape(['card' => ['name' => 'n'], 'after' => '!'])
        );
    }

    public function testSubSlotMapDerivesChildProps(): void
    {
        $badge = Compile::shape(span(Slot::text('label'))->class('badge'));
        $shape = Compile::shape(div(
            Slot::sub('user', $badge, static fn (array $d): array => ['label' => strtoupper((string)$d['name'])])
        ));

        $this->assertSame('<div><span class="badge">ADA</span></div>', $shape(['name' => 'ada']));
    }

    public function testEachSlotMapDerivesItemScope(): void
    {
        $item = Compile::shape(li(Slot::text('label')));
        $shape = Compile::shape(ul(
            Slot::each('items', $item, static fn (mixed $item): array => ['label' => (string)$item])
        ));

        $this->assertSame('<ul><li>a</li><li>b</li></ul>', $shape(['items' => ['a', 'b']]));
    }

    public function testNestedEachSlotsDoNotCollide(): void
    {
        $cell = Compile::shape(td(Slot::text('v')));
        $row = Compile::shape(tr(Slot::each('cells', $cell)));
        $shape = Compile::shape(table(Slot::each('rows', $row)));

        $this->assertSame(
            '<table><tr><td>1</td><td>2</td></tr><tr><td>3</td></tr></table>',
            $shape(['rows' => [
                ['cells' => [['v' => 1], ['v' => 2]]],
                ['cells' => [['v' => 3]]],
            ]])
        );
    }

    public function testInvalidUtf8IsSubstituted(): void
    {
        $shape = Compile::shape(div(Slot::text('value')));

        $this->assertSame("<div>caf\u{FFFD}</div>", $shape(['value' => "caf\xE9"]));
    }

    public function testNonStringableSlotValueIsRejected(): void
    {
        $shape = Compile::shape(div(Slot::text('value')));

        $this->expectException(InvalidArgumentException::class);

        $shape(['value' => ['array']]);
    }

    public function testNonArraySubValueIsRejected(): void
    {
        $card = Compile::shape(div(Slot::text('name')));
        $shape = Compile::shape(div(Slot::sub('card', $card)));

        $this->expectException(InvalidArgumentException::class);

        $shape(['card' => 'not-an-array']);
    }

    public function testNonIterableEachValueIsRejected(): void
    {
        $item = Compile::shape(li(Slot::text('title')));
        $shape = Compile::shape(ul(Slot::each('items', $item)));

        $this->expectException(InvalidArgumentException::class);

        $shape(['items' => 'not-iterable']);
    }

    public function testAttributeSlotInChildPositionIsRejected(): void
    {
        $this->expectException(LogicException::class);

        Compile::shape(div(Slot::attr('x')))->compile();
    }

    public function testTextSlotInAttributePositionIsRejected(): void
    {
        $this->expectException(LogicException::class);

        Compile::shape(div('x')->class(Slot::text('cls')))->compile();
    }

    public function testSlotTreesCannotBeRenderedDirectly(): void
    {
        $this->expectException(LogicException::class);

        div(Slot::text('title'))->render();
    }

    public function testSlotTreesCannotBeConvertedToDom(): void
    {
        $this->expectException(LogicException::class);

        div(Slot::text('title'))->toDom();
    }

    public function testToJsonDescribesSlots(): void
    {
        /** @var array{children: array<int, mixed>, class: mixed, tagName: string} $json */
        $json = div(Slot::text('title'))->class(Slot::attr('cls'))->toJSON();

        $this->assertSame(['slot' => 'title'], $json['children'][0]);
        $this->assertSame(['slot' => 'cls'], $json['class']);
    }

    public function testShapeCompilesOnlyOnce(): void
    {
        $shape = Compile::shape(div('x'));

        $this->assertSame($shape->compile(), $shape->compile());
        $this->assertSame($shape->id(), $shape->compile()->id());
    }

    public function testCompiledSourceContainsStaticMarkup(): void
    {
        $compiled = Compile::shape(div(span('static'))->class('x'))->compile();

        $this->assertStringContainsString("'<div class=\"x\"><span>static</span></div>'", $compiled->source());
        $this->assertSame(sha1($compiled->source()), $compiled->id());
    }

    public function testCompiledPrintWritesOutput(): void
    {
        $compiled = Compile::shape(div('hi'))->compile();

        ob_start();
        $compiled->print([]);
        $output = ob_get_clean();

        $this->assertSame('<div>hi</div>', $output);
    }

    public function testCompiledSaveWritesFile(): void
    {
        $path = sys_get_temp_dir() . '/purephp-compiled-test.html';
        $compiled = Compile::shape(div(XML::item('x')))->compile();

        $this->assertNotFalse($compiled->save($path, [], '<!DOCTYPE html>'));
        $this->assertSame('<!DOCTYPE html><div><item>x</item></div>', file_get_contents($path));

        unlink($path);
    }
}
