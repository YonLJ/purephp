<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * SVG elements that render self-closed when they are created without children,
 * keyed as a set for O(1) membership tests.
 *
 * SVG has no void elements: `<feTile />` and `<feTile></feTile>` describe the
 * same document, so self-closing is a rendering style. The list holds the
 * shape, animation, filter-primitive and light elements, which are childless in
 * practice; containers and text-bearing elements stay out, and childless use is
 * the only case where the short form is chosen, so elements that may legally
 * nest children (animate and animateMotion with mpath, use with title/desc)
 * keep working.
 *
 * @var array<string, true>
 */
const SELF_CLOSE_SVG_TAGS = [
    'animate' => true,
    'animateMotion' => true,
    'animateTransform' => true,
    'circle' => true,
    'ellipse' => true,
    'feBlend' => true,
    'feColorMatrix' => true,
    'feComposite' => true,
    'feConvolveMatrix' => true,
    'feDistantLight' => true,
    'feDisplacementMap' => true,
    'feDropShadow' => true,
    'feFlood' => true,
    'feFuncA' => true,
    'feFuncB' => true,
    'feFuncG' => true,
    'feFuncR' => true,
    'feGaussianBlur' => true,
    'feImage' => true,
    'feMergeNode' => true,
    'feMorphology' => true,
    'feOffset' => true,
    'fePointLight' => true,
    'feSpotLight' => true,
    'feTile' => true,
    'feTurbulence' => true,
    'image' => true,
    'line' => true,
    'mpath' => true,
    'path' => true,
    'polygon' => true,
    'polyline' => true,
    'rect' => true,
    'set' => true,
    'stop' => true,
    'use' => true,
    'view' => true,
];

class SVG extends XML
{
    /**
     * @internal Use the Pure\SVG functions or SVG::customTag() instead.
     *
     * @param string $tagName The SVG tag name.
     * @param array<int, mixed> $children
     */
    public function __construct(string $tagName, array $children = [])
    {
        parent::__construct($tagName, $children);
        // SVG element names are case-sensitive (feBlend, animateMotion), so the
        // set lookup is exact and the camelCase entries can match. Children win
        // over the style choice: self-closing would forbid legal nesting, and
        // setSelfClose(true) still rejects children when called explicitly.
        if ($children === [] && isset(SELF_CLOSE_SVG_TAGS[$tagName])) {
            $this->setSelfClose(true);
        }
    }

    /**
     * Factory method for creating SVG elements via static method calls.
     *
     * @param string $tag The tag name.
     * @param array<int, mixed> $children
     */
    public static function __callStatic(string $tag, array $children): SVG
    {
        return new SVG($tag, $children);
    }
}
