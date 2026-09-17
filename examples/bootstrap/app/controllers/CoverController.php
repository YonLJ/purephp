<?php declare(strict_types=1);

/**
 * The cover page controller: it renders the static view in views/cover.php,
 * which needs no slots and no compile step, so this page has neither a pure nor
 * a plain variant.
 *
 * @return string The rendered document.
 */
function coverController(): string
{
    ob_start();
    require __DIR__ . '/../../views/cover.php';

    return (string)ob_get_clean();
}
