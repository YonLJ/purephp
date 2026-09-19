<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * Standard attribute names used by the development guard.
 *
 * The list does not need to be complete: it is only consulted when a setter
 * receives a name no known attribute matches exactly, so a missing entry
 * weakens the typo detection but can never produce a warning on its own.
 *
 * @internal
 */
final class AttributeNames
{
    /**
     * The standard HTML, SVG and event attributes, in normalized spelling
     * (hyphens for HTML, camelCase for SVG).
     */
    private const KNOWN = [
        // Global HTML attributes.
        'accesskey', 'autocapitalize', 'autofocus', 'class', 'contenteditable', 'dir', 'draggable',
        'enterkeyhint', 'hidden', 'id', 'inert', 'inputmode', 'is', 'itemid', 'itemprop', 'itemref',
        'itemscope', 'itemtype', 'lang', 'nonce', 'part', 'popover', 'role', 'slot', 'spellcheck',
        'style', 'tabindex', 'title', 'translate',
        // Element attributes.
        'accept', 'accept-charset', 'action', 'align', 'allow', 'alt', 'as', 'async', 'autocomplete',
        'autoplay', 'blocking', 'charset', 'checked', 'cite', 'cols', 'colspan', 'content', 'controls',
        'coords', 'crossorigin', 'data', 'datetime', 'decoding', 'default', 'defer', 'dirname',
        'disabled', 'download', 'enctype', 'fetchpriority', 'for', 'form', 'formaction', 'headers',
        'height', 'high', 'href', 'hreflang', 'http-equiv', 'imagesizes', 'imagesrcset', 'integrity',
        'kind', 'label', 'list', 'loading', 'loop', 'low', 'manifest', 'max', 'maxlength', 'media',
        'method', 'min', 'minlength', 'multiple', 'muted', 'name', 'nomodule', 'novalidate', 'open',
        'optimum', 'pattern', 'ping', 'placeholder', 'playsinline', 'popovertarget',
        'popovertargetaction', 'poster', 'preload', 'readonly', 'referrerpolicy', 'rel', 'required',
        'reversed', 'rows', 'rowspan', 'sandbox', 'scope', 'selected', 'shadowrootmode', 'shape',
        'size', 'sizes', 'span', 'src', 'srcdoc', 'srclang', 'srcset', 'start', 'step', 'target',
        'type', 'usemap', 'value', 'width', 'wrap',
        // Event handler attributes.
        'onabort', 'onafterprint', 'onanimationend', 'onanimationstart', 'onauxclick', 'onbeforeprint',
        'onbeforeunload', 'onblur', 'oncancel', 'oncanplay', 'onchange', 'onclick', 'onclose',
        'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter',
        'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'ondurationchange', 'onended', 'onerror',
        'onfocus', 'onformdata', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onload',
        'onloadeddata', 'onloadedmetadata', 'onloadstart', 'onmousedown', 'onmouseenter',
        'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onpaste', 'onpause',
        'onplay', 'onplaying', 'onprogress', 'onratechange', 'onreset', 'onresize', 'onscroll',
        'onscrollend', 'onsecuritypolicyviolation', 'onseeked', 'onseeking', 'onselect', 'onslotchange',
        'onstalled', 'onsubmit', 'onsuspend', 'ontimeupdate', 'ontoggle', 'ontouchcancel', 'ontouchend',
        'ontouchmove', 'ontouchstart', 'ontransitioncancel', 'ontransitionend', 'ontransitionrun',
        'ontransitionstart', 'onvolumechange', 'onwaiting', 'onwheel',
        // SVG attributes and presentation attributes.
        'viewBox', 'preserveAspectRatio', 'xmlns', 'xmlns:xlink', 'xml:space', 'xml:lang', 'd', 'cx',
        'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points', 'transform', 'fill',
        'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'stroke-miterlimit', 'opacity',
        'clip-path', 'clip-rule', 'offset', 'stop-color', 'stop-opacity', 'gradientUnits',
        'gradientTransform', 'patternUnits', 'patternContentUnits', 'marker-start', 'marker-mid',
        'marker-end', 'markerWidth', 'markerHeight', 'refX', 'refY', 'orient', 'text-anchor',
        'dominant-baseline', 'font-family', 'font-size', 'font-weight', 'letter-spacing', 'pathLength',
        'vector-effect', 'filterUnits', 'primitiveUnits', 'stdDeviation', 'result', 'in', 'in2',
        'values', 'keyTimes', 'keySplines', 'calcMode', 'dur', 'begin', 'end', 'repeatCount',
        'attributeName', 'from', 'to', 'by', 'additive', 'accumulate',
    ];

    private function __construct()
    {
    }

    /**
     * The known attribute one edit away from $name, or null when $name is a
     * known attribute itself or no standard name is close.
     */
    public static function nearest(string $name): ?string
    {
        if (in_array($name, self::KNOWN, true)) {
            return null;
        }

        return Suggestion::nearest($name, self::KNOWN);
    }
}
