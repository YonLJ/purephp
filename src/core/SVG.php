<?php declare(strict_types=1);
namespace Tiny\Core;

const SELF_CLOSE_SVG_TAGS = [
    'animate',
    'animateMotion',
    'circle',
    'ellipse',
    'feBlend',
    'feColorMatrix',
    'feDisplacementMap',
    'feDropShadow',
    'feGaussianBlur',
    'feImage',
    'image',
    'line',
    'mpath',
    'path',
    'polygon',
    'polyline',
    'rect',
    'stop',
    'use'
];

class SVG extends XML {
    private function __construct(string $tagName, array $children)
    {
        parent::__construct($tagName, $children);
        if (in_array(strtolower($tagName), SELF_CLOSE_SVG_TAGS)) {
            $this->_selfClose(true);
        }
    }

    public static function __callStatic(string $tag, array $children): SVG
    {
        return new SVG($tag, $children);
    }

}
