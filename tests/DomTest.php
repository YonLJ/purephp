<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\Dom;
use Pure\Core\HTML;
use Pure\Core\XML;

use function Pure\HTML\div;
use function Pure\HTML\p;
use function Pure\Utils\rawHtml;
use function Pure\Utils\rawXml;

class DomTest extends TestCase
{
    public function testToDomReturnsDomElement(): void
    {
        $tag = div(p('one'), p('two'));
        $dom = $tag->toDom();

        $this->assertInstanceOf(DOMElement::class, $dom->toDom());
        $this->assertSame('div', $dom->toDom()->tagName);
        $this->assertSame(2, $dom->toDom()->getElementsByTagName('p')->length);
    }

    public function testAttributeValuesAreEscaped(): void
    {
        $tag = div('x')->href('/?a=1&b=2')->title('a & <b>');

        $this->assertSame(
            '<div href="/?a=1&amp;b=2" title="a &amp; &lt;b&gt;">x</div>',
            (string)$tag->toDom()
        );
    }

    public function testAttributeNamesAreNormalized(): void
    {
        $tag = div('x')->data_id('1');

        $this->assertSame('<div data-id="1">x</div>', (string)$tag->toDom());
    }

    public function testTextNodesAreEscaped(): void
    {
        $this->assertSame('<div>a &amp; b &lt; c</div>', (string)div('a & b < c')->toDom());
    }

    public function testUnicodeTextIsPreserved(): void
    {
        $this->assertSame('<div>你好 café</div>', (string)div('你好 café')->toDom());
    }

    public function testRawHtmlChildIsParsed(): void
    {
        $tag = div(rawHtml('<b>bold</b> & <i>italic</i>'));

        $this->assertSame('<div><b>bold</b> &amp; <i>italic</i></div>', (string)$tag->toDom());
    }

    public function testRawHtmlFragmentIsPreserved(): void
    {
        $tag = div(rawHtml('<li><a href="#">Contact</a></li>'));

        $this->assertSame('<div><li><a href="#">Contact</a></li></div>', (string)$tag->toDom());
    }

    public function testRawXmlChildIsParsed(): void
    {
        $xml = XML::root(rawXml('<child a="1"/>'));

        $this->assertSame('<root><child a="1"/></root>', (string)$xml->toDom());
    }

    public function testMalformedRawXmlThrows(): void
    {
        $this->expectException(Error::class);

        (string)XML::root(rawXml('<bad'))->toDom();
    }

    public function testEmptyRawXmlIsIgnored(): void
    {
        $this->assertSame('<root/>', (string)XML::root(rawXml(''))->toDom());
        $this->assertSame('<div></div>', (string)div(rawXml(''))->toDom());
    }

    public function testInvalidTagNameThrows(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('tag bad tag is invalid.');

        new Dom(new HTML('bad tag'));
    }

    public function testDomElementCanBeManipulated(): void
    {
        $tag = div(p('Original content'))->class('container');
        $dom = $tag->toDom();
        $domElement = $dom->toDom();

        /** @var DOMDocument $document */
        $document = $domElement->ownerDocument;

        /** @var DOMElement $newP */
        $newP = $document->createElement('p');
        $newP->appendChild($document->createTextNode('Added via DOM'));
        $domElement->appendChild($newP);

        $this->assertSame(
            '<div class="container"><p>Original content</p><p>Added via DOM</p></div>',
            (string)$dom
        );
    }
}
