<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Core\HTML;
use Pure\Core\SVG;

/**
 * Pins every tag factory to its tag name, so a renamed, deleted or
 * mistyped factory (htmlVar -> var, svgUse -> use, ...) fails here.
 */
class TagFactoryTest extends TestCase
{
    private const HTML_FUNCTIONS = [
        'a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base', 'bdi',
        'bdo', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col',
        'colgroup', 'data', 'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl',
        'dt', 'em', 'embed', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2',
        'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'htmlVar', 'i', 'iframe',
        'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'link', 'main', 'map', 'mark',
        'menu', 'meta', 'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup', 'option', 'output',
        'p', 'picture', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp',
        'script', 'section', 'select', 'slot', 'small', 'source', 'span', 'strong', 'style', 'sub',
        'summary', 'sup', 'table', 'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead',
        'time', 'title', 'tr', 'track', 'u', 'ul', 'video', 'wbr',
    ];

    private const SVG_FUNCTIONS = [
        'a', 'animate', 'animateMotion', 'animateTransform', 'circle', 'clipPath', 'defs', 'desc',
        'ellipse', 'feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite',
        'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight',
        'feDropShadow', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur',
        'feImage', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight',
        'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence', 'filter', 'foreignObject',
        'g', 'image', 'line', 'linearGradient', 'marker', 'mask', 'metadata', 'mpath', 'path',
        'pattern', 'polygon', 'polyline', 'radialGradient', 'rect', 'script', 'set', 'stop',
        'style', 'svg', 'svgSwitch', 'svgUse', 'symbol', 'text', 'textPath', 'title', 'tspan',
        'view',
    ];

    public function testEveryHtmlFactoryCreatesItsTag(): void
    {
        $this->assertSame(self::lowered(self::HTML_FUNCTIONS), self::declared('Pure\HTML'));

        foreach (self::HTML_FUNCTIONS as $name) {
            $tag = $name === 'htmlVar' ? 'var' : $name;
            $factory = "Pure\\HTML\\{$name}";
            $this->assertTrue(function_exists($factory), "{$factory}() is declared.");
            $element = $factory();

            $this->assertInstanceOf(HTML::class, $element, "{$factory}()");
            $this->assertSame($tag, $element->getTagName(), "{$factory}() tag name");
            $this->assertSame(
                isset(Pure\Core\SELF_CLOSE_HTML_TAGS[strtolower($tag)]),
                $element->getSelfClose(),
                "{$factory}() self-close flag"
            );
        }
    }

    public function testEverySvgFactoryCreatesItsTag(): void
    {
        $this->assertSame(self::lowered(self::SVG_FUNCTIONS), self::declared('Pure\SVG'));

        foreach (self::SVG_FUNCTIONS as $name) {
            $tag = $name === 'svgUse' ? 'use' : ($name === 'svgSwitch' ? 'switch' : $name);
            $factory = "Pure\\SVG\\{$name}";
            $this->assertTrue(function_exists($factory), "{$factory}() is declared.");
            $element = $factory();

            $this->assertInstanceOf(SVG::class, $element, "{$factory}()");
            $this->assertSame($tag, $element->getTagName(), "{$factory}() tag name");
            $this->assertSame(
                isset(Pure\Core\SELF_CLOSE_SVG_TAGS[$tag]),
                $element->getSelfClose(),
                "{$factory}() self-close flag"
            );
        }
    }

    /**
     * The declared factory names of a namespace, lowered and sorted. This PHP
     * build reports user function names in lowercase, so the comparison is
     * case-insensitive on both sides.
     *
     * @param string $namespace The namespace whose functions to list.
     * @return array<int, string>
     */
    private static function declared(string $namespace): array
    {
        $prefix = strtolower($namespace) . '\\';

        $functions = array_map(
            static fn (string $fqcn): string => strtolower(substr($fqcn, strlen($prefix))),
            array_filter(
                get_defined_functions()['user'],
                static fn (string $fqcn): bool => str_starts_with($fqcn, $prefix)
            )
        );
        sort($functions);

        return $functions;
    }

    /**
     * @param array<int, string> $names
     * @return array<int, string>
     */
    private static function lowered(array $names): array
    {
        $lowered = array_map(static fn (string $name): string => strtolower($name), $names);
        sort($lowered);

        return $lowered;
    }
}
