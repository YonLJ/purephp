<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\XML;

function Address(array $props)
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

    private function createInstance()
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

    public function testToString()
    {
        $expectedStr = '<customers><customer id="55000"><name>Charter Group</name><address><street>100 Main</street><city>Framingham</city><state>MA</state><zip>01701</zip></address><address><street>720 Prospect</street><city>Framingham</city><state>MA</state><zip>01701</zip></address><address><street>120 Ridge</street><state>MA</state><zip>01760</zip></address></customer></customers>';
        $this->assertSame($expectedStr, (string)$this->xml);
    }

    public function testSave()
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
}
