<?php

declare(strict_types=1);

namespace Pure\Utils;

use Pure\Component\Call;
use Pure\Core\HTML;
use Pure\Core\Tag;
use Pure\Core\XML;

/**
 * Join class name arguments into a class attribute value.
 *
 * Kept: non-empty strings (including the string "0") and numbers.
 * Dropped: null, booleans, and empty strings. Arrays contribute their
 * string/number list entries, or their keys for map entries with a truthy
 * value.
 *
 * @param array<int|string, mixed>|bool|int|float|string|null ...$args
 */
function clx(array|bool|int|float|string|null ...$args): string|null
{
    $classList = [];

    foreach ($args as $className) {
        if (is_array($className)) {
            foreach ($className as $key => $value) {
                if (is_int($key)) {
                    // List entry: keep non-empty strings and numbers.
                    if (is_string($value) && $value !== '') {
                        $classList[] = $value;
                    } elseif (is_int($value) || is_float($value)) {
                        $classList[] = (string)$value;
                    }

                    continue;
                }

                // Map entry: a truthy value keeps the key as a class name.
                if ($key !== '' && !empty($value)) {
                    $classList[] = $key;
                }
            }

            continue;
        }

        if (is_bool($className) || $className === null || $className === '') {
            continue;
        }

        $classList[] = is_string($className) ? $className : (string)$className;
    }

    return $classList === [] ? null : implode(' ', $classList);
}

/**
 * Join style declarations into an inline style attribute value.
 *
 * @param array<array-key, mixed>|null $list
 * @return string|null The joined style string, or null if empty.
 */
function sty(array|null $list): string|null
{
    if (empty($list)) {
        return null;
    }

    /** @var string[] */
    $styleList = [];
    foreach ($list as $key => $val) {
        if (is_string($key) && (is_string($val) || is_numeric($val))) {
            $styleList[] = "$key: $val";
        }
    }

    if (empty($styleList)) {
        return null;
    }

    return join('; ', $styleList) . ';';
}

/**
 * Render a tag tree or a component call as a complete HTML document.
 *
 * The `<!DOCTYPE html>` header is prepended to the markup, so the result is
 * ready to be echoed as a page:
 *
 *     echo renderHTML(html(body(h1('Hello'))));
 *
 * A fragment gets the same header; use `->render()` when only the markup is
 * wanted.
 *
 * @param Tag|Call $node The tree or call to render.
 * @return string The header followed by the rendered markup.
 */
function renderHTML(Tag|Call $node): string
{
    return HTML::DOCUMENT_HEADER . $node->render();
}

/**
 * Render a tag tree or a component call as a standalone XML document.
 *
 * The `<?xml version="1.0"?>` declaration is prepended to the markup:
 *
 *     echo renderXML(XML::customers(XML::customer(XML::name('Charter Group'))));
 *
 * @param Tag|Call $node The tree or call to render.
 * @return string The declaration followed by the rendered markup.
 */
function renderXML(Tag|Call $node): string
{
    return XML::DOCUMENT_HEADER . $node->render();
}
