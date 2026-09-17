<?php declare(strict_types=1);

require_once __DIR__ . '/ColLogo.php';
require_once __DIR__ . '/ColLinks.php';

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * The page footer: the logo column and the link columns.
 *
 * @param array{src: string, width: string, height: string, text: string} $logo
 * @param list<array{title: string, links: list<array{text: string, href: string}>}> $columns
 */
function PageFooter(array $logo, array $columns): Raw
{
    $links = [];

    foreach ($columns as $column) {
        $links[] = (string)ColLinks($column);
    }

    return render(
        __DIR__ . '/PageFooter.shape.php',
        logo: (string)ColLogo($logo),
        columns: implode('', $links),
    );
}
