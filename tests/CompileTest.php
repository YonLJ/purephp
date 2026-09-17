<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\SlotRuntime;
use Pure\Compile\Shape;
use Pure\Core\HTML;
use Pure\Core\MissingSlotException;
use Pure\Core\Raw;
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

use ReflectionClassConstant;
use ReflectionProperty;

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
        $tree = div(Raw::of('<b>bold</b> & raw'), 'plain & text');

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

    public function testNullTextSlotDoesNotEmitDeprecations(): void
    {
        $shape = Compile::shape(div(Slot::text('value')));

        set_error_handler(static function (int $severity, string $message): bool {
            throw new ErrorException($message, 0, $severity);
        });

        try {
            $this->assertSame('<div></div>', $shape(['value' => null]));
        } finally {
            restore_error_handler();
        }
    }

    public function testScalarTextSlotValuesMatchTheRuntimeHelper(): void
    {
        $shape = Compile::shape(div(Slot::text('value')));
        $cases = [[0, '0'], [1, '1'], [-3, '-3'], [2.5, '2.5'], [true, '1'], [false, ''], ['', ''], ['0', '0'], ['a & b', 'a &amp; b']];

        foreach ($cases as [$value, $expected]) {
            $this->assertSame('<div>' . $expected . '</div>', $shape(['value' => $value]));
            // The inline scalar path must stay byte-identical to the helper.
            $this->assertSame('<div>' . SlotRuntime::text($value, 'value') . '</div>', $shape(['value' => $value]));
        }
    }

    public function testNullAttributeSlotOmitsTheAttribute(): void
    {
        $shape = Compile::shape(div('x')->class(Slot::attr('cls')));

        $this->assertSame('<div>x</div>', $shape(['cls' => null]));
        $this->assertSame('<div class="a">x</div>', $shape(['cls' => 'a']));
    }

    public function testBooleanAttributeSlotsMatchStaticAttributes(): void
    {
        $staticFalse = Compile::shape(HTML::input()->disabled(false));
        $staticTrue = Compile::shape(HTML::input()->disabled(true));
        $slotted = Compile::shape(HTML::input()->disabled(Slot::attr('disabled')));

        $this->assertSame('<input />', $staticFalse([]));
        $this->assertSame('<input disabled="disabled" />', $staticTrue([]));
        $this->assertSame($staticFalse([]), $slotted(['disabled' => false]));
        $this->assertSame($staticTrue([]), $slotted(['disabled' => true]));
        $this->assertSame('<input disabled="disabled" />', $slotted(['disabled' => 'disabled']));
    }

    public function testStringableSlotValueIsStringified(): void
    {
        $value = new class () {
            public function __toString(): string
            {
                return 'a & b';
            }
        };

        $shape = Compile::shape(div(Slot::text('value')));

        $this->assertSame('<div>a &amp; b</div>', $shape(['value' => $value]));
        // The non-scalar fallback must render exactly like its string form.
        $this->assertSame($shape(['value' => 'a & b']), $shape(['value' => $value]));
    }

    public function testTextSlotEscapingUsesTheSharedEscaperConstants(): void
    {
        $source = Compile::shape(div(Slot::text('title')))->compile()->source;

        // Escaping config stays owned by Escaper: generated code references the
        // shared constants instead of copying the flag values. Whether the
        // scalar path is inlined is a benchmark concern, not a unit-test
        // contract; the behaviour tests above cover the runtime fallback.
        $this->assertStringContainsString('\Pure\Core\Escaper::FLAGS', $source);
        $this->assertStringContainsString('\Pure\Core\Escaper::ENCODING', $source);
    }

    public function testTagLikeTextIsEscapedOnBothPaths(): void
    {
        $static = div('a<b', '2<3', '<p>x</p>');

        $this->assertSame('<div>a&lt;b2&lt;3&lt;p&gt;x&lt;/p&gt;</div>', $static->render());
        $this->assertSame($static->render(), Compile::shape($static)([]));

        // Same text next to a slot, so the walker emits it instead of folding.
        $this->assertSame(
            '<div>sa&lt;b2&lt;3&lt;p&gt;x&lt;/p&gt;</div>',
            Compile::shape(div(Slot::text('v'), 'a<b', '2<3', '<p>x</p>'))(['v' => 's'])
        );
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
        try {
            Compile::shape(div(Slot::attr('x')))->compile();
            $this->fail('Expected LogicException to be thrown.');
        } catch (LogicException $e) {
            $this->assertSame("attribute slots are not allowed in child position: 'x'.", $e->getMessage());
        }
    }

    public function testTextSlotInAttributePositionIsRejected(): void
    {
        try {
            Compile::shape(div('x')->class(Slot::text('cls')))->compile();
            $this->fail('Expected LogicException to be thrown.');
        } catch (LogicException $e) {
            $this->assertSame(
                "only attribute slots are allowed in attribute position, got 'Text' for 'cls'.",
                $e->getMessage()
            );
        }
    }

    public function testSlotTreesCannotBeRenderedDirectly(): void
    {
        try {
            div(Slot::text('title'))->render();
            $this->fail('Expected LogicException to be thrown.');
        } catch (LogicException $e) {
            $this->assertSame(
                'Tag trees containing slots cannot be rendered directly; use Pure\Compile\Compile::shape() and render with data.',
                $e->getMessage()
            );
        }
    }

    public function testToJsonDescribesSlots(): void
    {
        $json = div(Slot::text('title'))->class(Slot::attr('cls'))->toJSON();

        $this->assertSame('div', $json['tagName']);
        $this->assertSame(['slot' => 'title'], $json['children'][0]);
        $this->assertSame(['slot' => 'cls'], $json['attrs']['class']);
    }

    public function testShapeCompilesOnlyOnce(): void
    {
        $shape = Compile::shape(div('x'));

        $this->assertSame($shape->compile(), $shape->compile());
        $this->assertSame($shape->id(), $shape->compile()->id);
    }

    public function testCompiledSourceContainsStaticMarkup(): void
    {
        $shape = Compile::shape(div(span('static'))->class('x'));
        $compiled = $shape->compile();

        $this->assertStringContainsString("'<div class=\"x\"><span>static</span></div>'", $compiled->source);
        $this->assertSame($shape->id(), $compiled->id);
    }

    public function testCompiledRenderReturnsOutput(): void
    {
        $compiled = Compile::shape(div(Slot::text('title')))->compile();

        $this->assertSame('<div>hi</div>', $compiled->render(['title' => 'hi']));
    }

    public function testCompiledSaveWritesFile(): void
    {
        $path = sys_get_temp_dir() . '/purephp-compiled-test.html';
        $compiled = Compile::shape(div(XML::item('x')))->compile();

        $this->assertNotFalse($compiled->save($path, [], '<!DOCTYPE html>'));
        $this->assertSame('<!DOCTYPE html><div><item>x</item></div>', file_get_contents($path));

        unlink($path);
    }

    public function testLiteralAndSlotEscapingStayByteIdentical(): void
    {
        $attribute = 'a & b " c';
        $text = 'a & b < c &copy;';

        $literalAttr = Compile::shape(div('x')->title($attribute));
        $slottedAttr = Compile::shape(div('x')->title(Slot::attr('value')));
        $this->assertSame($literalAttr([]), $slottedAttr(['value' => $attribute]));

        $literalText = Compile::shape(p($text));
        $slottedText = Compile::shape(p(Slot::text('value')));
        $this->assertSame($literalText([]), $slottedText(['value' => $text]));
    }

    public function testStaticSubtreesAreFoldedFromRender(): void
    {
        $shape = Compile::shape(div(
            Slot::text('title'),
            div(span('static & more < 10'))->class('note')
        ));

        $this->assertSame(
            '<div>t<div class="note"><span>static &amp; more &lt; 10</span></div></div>',
            $shape(['title' => 't'])
        );
    }

    public function testSlotFreeShapeCompilesToASingleLiteral(): void
    {
        $source = Compile::shape(div(span('static & more < 10'))->class('note'))->compile()->source;

        $this->assertStringNotContainsString('SlotRuntime::', $source);
        $this->assertSame(1, substr_count($source, '$out .='));
    }

    public function testStaticSubShapeStillValidatesItsSlot(): void
    {
        $shape = Compile::shape(div(Slot::sub('child', Compile::shape(span('static')))));

        $this->assertSame('<div><span>static</span></div>', $shape(['child' => []]));

        try {
            $shape([]);
            $this->fail('Expected MissingSlotException to be thrown.');
        } catch (MissingSlotException $e) {
            $this->assertSame("slot 'child' is required but was not provided.", $e->getMessage());
        }
    }

    public function testStaticEachItemStillValidatesItems(): void
    {
        $shape = Compile::shape(ul(Slot::each('items', Compile::shape(li('x')))));

        $this->assertSame('<ul><li>x</li><li>x</li></ul>', $shape(['items' => [[], []]]));

        $this->expectException(InvalidArgumentException::class);

        $shape(['items' => 'nope']);
    }

    public function testShapeSavePrependsTheDocumentHeader(): void
    {
        $path = sys_get_temp_dir() . '/purephp-shape-save.html';

        $this->assertNotFalse(Compile::shape(div(Slot::text('title')))->save($path, ['title' => 'hi']));
        $this->assertSame('<!DOCTYPE html><div>hi</div>', file_get_contents($path));

        $this->assertNotFalse(
            Compile::shape(div(Slot::text('title')))->save($path, ['title' => 'hi'], '<!-- custom -->')
        );
        $this->assertSame('<!-- custom --><div>hi</div>', file_get_contents($path));
    }

    public function testShapeSaveUsesTheXmlDeclarationForXmlRoots(): void
    {
        $path = sys_get_temp_dir() . '/purephp-shape-save.xml';

        $this->assertNotFalse(Compile::shape(XML::root(Slot::text('v')))->save($path, ['v' => 'x']));
        $this->assertSame('<?xml version="1.0"?><root>x</root>', file_get_contents($path));
    }

    public function testRebuiltShapeRendersItsOwnData(): void
    {
        $tree = static fn () => div(Slot::text('title'));

        $first = Compile::shape($tree());
        $second = Compile::shape($tree());

        $this->assertSame('<div>a</div>', $first(['title' => 'a']));
        $this->assertSame('<div>b</div>', $second(['title' => 'b']));
        $this->assertSame('<div>c</div>', $first(['title' => 'c']));
    }

    public function testRebuiltShapeWithAMapUsesTheLiveClosure(): void
    {
        $make = static function (string $suffix): Shape {
            return Compile::shape(
                div(
                    Slot::sub(
                        'child',
                        Compile::shape(span(Slot::text('x'))),
                        static fn (array $data): array => ['x' => (string)$data['v'] . $suffix]
                    )
                )
            );
        };

        $first = $make('-a');
        $second = $make('-b');

        $this->assertSame('<div><span>v-a</span></div>', $first(['v' => 'v']));
        $this->assertSame('<div><span>v-b</span></div>', $second(['v' => 'v']));
        $this->assertSame('<div><span>v-c</span></div>', $make('-c')(['v' => 'v']));
    }

    public function testSourceMemoDropsTheOldestEntriesBeyondItsByteBudget(): void
    {
        $limit = (new ReflectionClassConstant(Compile::class, 'MEMO_BYTES'))->getValue();
        if (!is_int($limit)) {
            $this->fail('Compile::MEMO_BYTES must be an int.');
        }

        $sources = new ReflectionProperty(Compile::class, 'sources');
        $bytes = new ReflectionProperty(Compile::class, 'memoBytes');

        $seed = 'static function (array $v, array $maps): string { return \'\'; }';
        $sources->setValue(null, ['seed' => $seed]);
        $bytes->setValue(null, $limit + strlen($seed));

        $this->assertSame(
            '<div><span>x</span></div>',
            Compile::shape(div(span(Slot::text('memo'))))(['memo' => 'x'])
        );

        /** @var array<string, string> $remaining */
        $remaining = $sources->getValue();
        $this->assertArrayNotHasKey('seed', $remaining);
        $this->assertLessThanOrEqual($limit, $bytes->getValue());
    }
}
