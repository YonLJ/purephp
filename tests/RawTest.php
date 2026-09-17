<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\Raw;

class RawTest extends TestCase
{
    public function testRawValueIsPubliclyReadable(): void
    {
        $raw = Raw::of('<b>x</b>');

        $this->assertInstanceOf(Raw::class, $raw);
        $this->assertSame('<b>x</b>', $raw->value);
    }

    public function testRawContentIsEmittedVerbatim(): void
    {
        $this->assertSame('<b>x</b>', (string)Raw::of('<b>x</b>'));
        $this->assertSame('<x/>', (string)Raw::of('<x/>'));
    }
}
