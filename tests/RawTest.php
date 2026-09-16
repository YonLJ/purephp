<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\RawType;

use function Pure\Utils\rawHtml;
use function Pure\Utils\rawXml;

class RawTest extends TestCase
{
    public function testRawTypeIsAutoloadableOnItsOwn(): void
    {
        $this->assertSame('HTML', RawType::HTML->name);
        $this->assertSame('XML', RawType::XML->name);
    }

    public function testRawContentIsEmittedVerbatim(): void
    {
        $this->assertSame('<b>x</b>', (string)rawHtml('<b>x</b>'));
        $this->assertSame('<x/>', (string)rawXml('<x/>'));
    }

    public function testToJsonUsesTheEnumName(): void
    {
        $this->assertSame(['type' => 'HTML', 'content' => '<b>x</b>'], rawHtml('<b>x</b>')->toJSON());
        $this->assertSame(['type' => 'XML', 'content' => '<x/>'], rawXml('<x/>')->toJSON());
    }
}
