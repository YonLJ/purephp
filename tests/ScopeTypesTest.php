<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ScopeTypes;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\li;
use function Pure\HTML\span;
use function Pure\HTML\ul;

final class ScopeTypesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Compile::cachePath(null);
        Compile::flush();
    }

    public function testStaticTreesHaveNoAnnotations(): void
    {
        $this->assertSame('', ScopeTypes::docblock(Compile::shape(div('static'))->tree()));
    }

    public function testValueConditionAndOddSlotsAreDeclared(): void
    {
        $tree = Compile::shape(div(
            Slot::text('title'),
            Slot::text('subtitle')->default('none'),
            Slot::if('city', span(Slot::text('city'))),
            Slot::text('user-name')
        ))->tree();

        $this->assertSame(
            "/**\n"
            . " * @var scalar|null|\\Stringable \$title\n"
            . " * @var scalar|null|\\Stringable \$subtitle\n"
            . " * @var scalar|null|\\Stringable \$city\n"
            . " * @var array{title: scalar|null|\\Stringable, subtitle: scalar|null|\\Stringable, city: scalar|null|\\Stringable, 'user-name': scalar|null|\\Stringable} \$data\n"
            . ' */',
            ScopeTypes::docblock($tree)
        );
    }

    public function testEachKindItemsShareOneFlattenedShape(): void
    {
        $tree = Compile::shape(ul(Slot::eachKind('blocks', [
            'text' => li(Slot::text('value')),
            'link' => li(Slot::text('value'))->class(Slot::attr('class')),
        ])))->tree();

        $this->assertSame(
            "/**\n"
            . " * @var iterable<array-key, array{kind?: 'text'|'link', value: scalar|null|\\Stringable, class: scalar|null|\\Stringable}> \$blocks\n"
            . ' */',
            ScopeTypes::docblock($tree)
        );
    }

    public function testIfBranchesShareTheCurrentScope(): void
    {
        $tree = Compile::shape(div(
            Slot::if('flag', span(Slot::text('then')), span(Slot::text('else')))
        ))->tree();

        $this->assertSame(
            "/**\n"
            . " * @var mixed \$flag\n"
            . " * @var scalar|null|\\Stringable \$then\n"
            . " * @var scalar|null|\\Stringable \$else\n"
            . ' */',
            ScopeTypes::docblock($tree)
        );
    }

    public function testOptionalContainersMayBeNull(): void
    {
        $tree = Compile::shape(div(
            Slot::child('box', span('static'))->required(false),
            Slot::each('list', li(Slot::text('value')))->required(false)
        ))->tree();

        $this->assertSame(
            "/**\n"
            . " * @var array<array-key, mixed>|null \$box\n"
            . " * @var iterable<array-key, array{value: scalar|null|\\Stringable}>|null \$list\n"
            . ' */',
            ScopeTypes::docblock($tree)
        );
    }

    public function testAConditionKeyThatIsAlsoAValueDropsMixed(): void
    {
        // One key read two ways: `mixed` would swallow the union in an analyzer,
        // so the value type is what survives.
        $tree = Compile::shape(div(
            Slot::if('mode', span('a'), span('b')),
            Slot::text('mode')->default('m')
        ))->tree();

        $this->assertSame(
            "/**\n"
            . " * @var scalar|null|\\Stringable \$mode\n"
            . ' */',
            ScopeTypes::docblock($tree)
        );
    }
}
