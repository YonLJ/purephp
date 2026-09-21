<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;

use function Pure\Component\component;

use Pure\Component\Registry;
use Pure\Core\XML;

use function Pure\HTML\{body, div, html};
use function Pure\Utils\{clx, renderHTML, renderXML, sty};

class UtilsTest extends TestCase
{
    public function testClx(): void
    {
        $this->assertEquals('class-a class-b class-c class-d', clx(
            'class-a',
            'class-b',
            [
                'class-c',
                'class-d' => true,
                'class-e' => false,
            ]
        ));
        $this->assertEquals('class-a class-b class-c', clx(
            null,
            '',
            [
                'class-a',
                'class-b',
            ],
            [
                'class-c' => true,
                null,
            ]
        ));
        $this->assertSame('col-6 col-md-4', clx('col-6', 'col-md-4'));
        $this->assertSame('col-1 2', clx('col-1', 2));
        $this->assertSame('col-1 2', clx(['col-1', 2]));
        $this->assertNull(clx());
        $this->assertNull(clx(null, '', []));
    }

    public function testClxDropsBooleansAndKeepsExplicitStringZero(): void
    {
        // Deliberately not a literal so static analysis cannot narrow the branch.
        $flag = getenv('PUREPHP_TEST_FLAG') !== false;

        $this->assertSame('btn', clx('btn', $flag ? 'active' : false));
        $this->assertSame('btn active', clx('btn', 'active'));
        $this->assertNull(clx(false));
        $this->assertNull(clx(true));
        $this->assertSame('0', clx('0'));
        $this->assertSame('0', clx(['0']));
    }

    public function testClxDropsNonStringArrayEntries(): void
    {
        // List entries: only non-empty strings and numbers survive.
        $this->assertNull(clx([true, null, ['nested'], '']));
        $this->assertSame('1 2.5', clx([1, 2.5]));
        $this->assertSame('0', clx([0]));
        $this->assertSame('0', clx([0.0]));
        $this->assertSame('zero 5', clx([0 => 'zero', 5]));

        // Map entries: the key survives only for truthy, non-empty-string values,
        // and a numeric-string key has already become an int key.
        $this->assertNull(clx(['zero' => 0, 'strict-zero' => '0', 'empty' => '', 'false' => false, 'none' => null]));
        $this->assertNull(clx(['0' => true]));
        $this->assertSame('a b', clx(['a' => 'yes', 'b' => 1]));
    }

    public function testSty(): void
    {
        $this->assertEquals('background-color: red; height: 36px; border: 1px solid #fff;', sty([
            'background-color' => 'red',
            'height' => '36px',
            'border' => '1px solid #fff',
            'line-height' => false,
            'color' => null,
        ]));
    }

    public function testRenderHTMLPrependsTheDoctype(): void
    {
        $this->assertSame(
            '<!DOCTYPE html><html lang="en"><body><div>Hello</div></body></html>',
            renderHTML(html(body(div('Hello')))->lang('en'))
        );
    }

    public function testRenderHTMLKeepsFragmentsIntactBehindTheHeader(): void
    {
        $this->assertSame('<!DOCTYPE html><div>&lt;b&gt;</div>', renderHTML(div('<b>')));
    }

    public function testRenderHTMLRendersComponentCalls(): void
    {
        self::registerUnit('UtilsPage', static fn (): \Pure\Compile\Shape => Compile::shape(
            html(body(div('Hi')))
        ));

        $this->assertSame(
            '<!DOCTYPE html><html><body><div>Hi</div></body></html>',
            renderHTML(component('UtilsPage'))
        );
    }

    public function testRenderXMLPrependsTheDeclaration(): void
    {
        $this->assertSame(
            '<?xml version="1.0"?><customers><customer id="55000"><name>Charter Group</name></customer></customers>',
            renderXML(XML::customers(XML::customer(XML::name('Charter Group'))->id('55000')))
        );
    }

    public function testRenderXMLRendersComponentCalls(): void
    {
        self::registerUnit('UtilsXml', static fn (): \Pure\Compile\Shape => Compile::shape(
            XML::customers(XML::name('Charter Group'))
        ));

        $this->assertSame(
            '<?xml version="1.0"?><customers><name>Charter Group</name></customers>',
            renderXML(component('UtilsXml'))
        );
    }

    /**
     * Register a unit for this test class. The registry allows one component
     * per unit file, so every name gets its own anchor file.
     *
     * @param \Closure(): \Pure\Compile\Shape $factory
     */
    private static function registerUnit(string $name, \Closure $factory): void
    {
        if (in_array($name, Registry::names(), true)) {
            return;
        }

        $file = sys_get_temp_dir() . '/purephp-utils-' . $name . '.cmp.php';

        if (!is_file($file)) {
            file_put_contents($file, "<?php\n");
            register_shutdown_function(static fn () => @unlink($file));
        }

        Registry::register($name, $file, $factory);
    }
}
