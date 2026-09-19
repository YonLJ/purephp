<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, footer};

require_once __DIR__ . '/../components/ColLogo.cmp.php';
require_once __DIR__ . '/../components/ColLinks.cmp.php';
require_once __DIR__ . '/../app/services/PricingService.php';

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
 * The page footer: it fetches the logo and the link columns from the service
 * and renders its own children.
 */
function PageFooter(): string
{
    $data = PricingService::footer();

    return render(
        'PageFooter',
        logo: ColLogo(...$data['logo']),
        columns: array_map(
            static fn (array $column): string => ColLinks($column['title'], $column['links']),
            $data['columns']
        ),
    );
}
