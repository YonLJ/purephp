<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use function Pure\Utils\clx;
use function Pure\Utils\sty;

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
}
