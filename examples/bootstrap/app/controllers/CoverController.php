<?php declare(strict_types=1);

require_once __DIR__ . '/../../views/cover.php';

/**
 * The cover page controller: the page function renders the static view, which
 * needs no slots and no compile step, so this page has neither a pure nor a
 * plain variant.
 *
 * @return string The rendered document.
 */
function coverController(): string
{
    return (string)coverPage();
}
