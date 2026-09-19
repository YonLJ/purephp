<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, footer};

require_once __DIR__ . '/ColLogo.cmp.php';
require_once __DIR__ . '/ColLinks.cmp.php';

/**
 * The page footer template: the logo column and the link columns, rendered by
 * ColLogo() and ColLinks() and injected as raw markup.
 */
register('PageFooter', __FILE__, static fn () =>
    footer(
        div(
            Slot::raw('logo'),
            Slot::raw('columns')
        )->class('row'),
    )->class('pt-4 my-md-5 pt-md-5 border-top')
);

/**
 * The page footer: the logo column and the link columns.
 *
 * @param array{src: string, width: string, height: string, text: string} $logo
 * @param list<array{title: string, links: list<array{text: string, href: string}>}> $columns
 */
function PageFooter(array $logo, array $columns): string
{
    $links = [];

    foreach ($columns as $column) {
        $links[] = ColLinks($column);
    }

    return render(
        'PageFooter',
        logo: ColLogo($logo),
        columns: $links,
    );
}
