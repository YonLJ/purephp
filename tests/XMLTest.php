<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\XML;

use function Pure\Utils\rawXml;

/** @param array<string, string> $props */
function Address(array $props): XML
{
    extract($props);

    return (
        XML::address(
            empty($street) ? null : XML::street($street),
            empty($city) ? null : XML::city($city),
            empty($state) ? null : XML::state($state),
            empty($zip) ? null : XML::zip($zip)
        )
    );
}

class XMLTest extends TestCase
{
    /** @var array<int, array<string, string>> */
    private array $testData = [
        [
            'street' => '100 Main',
            'city' => 'Framingham',
            'state' => 'MA',
            'zip' => '01701',
        ],
        [
            'street' => '720 Prospect',
            'city' => 'Framingham',
            'state' => 'MA',
            'zip' => '01701',
        ],
        [
            'street' => '120 Ridge',
            'state' => 'MA',
            'zip' => '01760',
        ],
    ];

    private XML $xml;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->xml = $this->createInstance();
    }

    private function createInstance(): XML
    {
        return (
            XML::customers(
                XML::customer(
                    XML::name('Charter Group'),
                    array_map(fn ($x) => Address($x), $this->testData)
                )->id('55000')
            )
        );
    }

    public function testToString(): void
    {
        $expectedStr = '<customers><customer id="55000"><name>Charter Group</name><address><street>100 Main</street><city>Framingham</city><state>MA</state><zip>01701</zip></address><address><street>720 Prospect</street><city>Framingham</city><state>MA</state><zip>01701</zip></address><address><street>120 Ridge</street><state>MA</state><zip>01760</zip></address></customer></customers>';
        $this->assertSame($expectedStr, (string)$this->xml);
    }

    public function testSave(): void
    {
        $outputPath = './output.xml';

        $result = $this->xml->toSave($outputPath);
        $this->assertNotFalse($result);
        $this->assertFileExists($outputPath);

        $savedContent = file_get_contents($outputPath);
        $expectedStr = '<?xml version="1.0"?><customers><customer id="55000"><name>Charter Group</name><address><street>100 Main</street><city>Framingham</city><state>MA</state><zip>01701</zip></address><address><street>720 Prospect</street><city>Framingham</city><state>MA</state><zip>01701</zip></address><address><street>120 Ridge</street><state>MA</state><zip>01760</zip></address></customer></customers>';
        $this->assertSame($expectedStr, $savedContent);

        unlink($outputPath);
    }

    public function testMagicStaticMethod(): void
    {
        // Test magic static method approach
        $xml = XML::root(
            XML::item('Content 1'),
            XML::item('Content 2')
        );

        $this->assertSame('root', $xml->getTagName());
        $this->assertCount(2, $xml->getChildren());
    }

    public function testConstructorMethod(): void
    {
        // Test constructor approach
        $xml = new XML('custom-root', [
            new XML('custom-item', ['Custom Content 1']),
            new XML('custom-item', ['Custom Content 2']),
        ]);

        $this->assertSame('custom-root', $xml->getTagName());
        $this->assertCount(2, $xml->getChildren());
    }

    public function testStringTagsAreFiltered(): void
    {
        // Test that string XML tags in children are filtered out
        $xml = XML::root('<item>This should be filtered</item>', '<data>This too</data>');

        $output = (string)$xml;

        // The XML tags should be stripped, only text content remains
        $this->assertStringNotContainsString('<item>', $output);
        $this->assertStringNotContainsString('</item>', $output);
        $this->assertStringNotContainsString('<data>', $output);
        $this->assertStringNotContainsString('</data>', $output);
        $this->assertStringContainsString('This should be filtered', $output);
        $this->assertStringContainsString('This too', $output);
    }

    public function testRawXmlPreservesContent(): void
    {
        // Test that rawXml preserves XML content
        $xml = XML::root(
            rawXml('<item>This should be preserved</item>'),
            rawXml('<data>This too</data>')
        );

        $output = (string)$xml;

        // The XML tags should be preserved
        $this->assertStringContainsString('<item>This should be preserved</item>', $output);
        $this->assertStringContainsString('<data>This too</data>', $output);
    }
}
