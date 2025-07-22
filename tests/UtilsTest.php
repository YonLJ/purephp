<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use function Pure\Utils\clx;
use function Pure\Utils\sty;

class UtilsTest extends TestCase
{
    public function testClx()
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
    }

    public function testSty()
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
