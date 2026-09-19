<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h1, p};

/**
 * The pricing page heading template.
 */
register('PricingHeader', __FILE__, static fn () =>
    div(
        h1(Slot::value('title'))->class('display-4'),
        p(Slot::value('desc'))->class('lead')
    )->class('pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center')
);

/**
 * The pricing page heading.
 */
function PricingHeader(string $title, string $desc): string
{
    return render(
        'PricingHeader',
        title: $title,
        desc: $desc,
    );
}
