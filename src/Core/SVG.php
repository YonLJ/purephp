<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * Self-closing SVG elements, keyed as a set for O(1) membership tests.
 *
 * @var array<string, true>
 */
const SELF_CLOSE_SVG_TAGS = [
    'animate' => true,
    'animateMotion' => true,
    'circle' => true,
    'ellipse' => true,
    'feBlend' => true,
    'feColorMatrix' => true,
    'feDisplacementMap' => true,
    'feDropShadow' => true,
    'feGaussianBlur' => true,
    'feImage' => true,
    'image' => true,
    'line' => true,
    'mpath' => true,
    'path' => true,
    'polygon' => true,
    'polyline' => true,
    'rect' => true,
    'stop' => true,
    'use' => true,
];

class SVG extends XML
{
    /**
     * Initialize an SVG element. Self-closing tags are detected automatically.
     *
     * @param string $tagName The SVG tag name.
     * @param array<int, mixed> $children
     */
    public function __construct(string $tagName, array $children = [])
    {
        parent::__construct($tagName, $children);
        // SVG element names are case-sensitive (feBlend, animateMotion), so the
        // set lookup is exact and the camelCase entries can match.
        if (isset(SELF_CLOSE_SVG_TAGS[$tagName])) {
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
