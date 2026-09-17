<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h1, p};

/**
 * The pricing page heading template.
 */
register('PricingHeader', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h1(Slot::text('title'))->class('display-4'),
        p(Slot::text('desc'))->class('lead')
    )->class('pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center')
));

/**
 * The pricing page heading.
 */
function PricingHeader(string $title, string $desc): Raw
{
    return render(
        'PricingHeader',
        title: $title,
        desc: $desc,
    );
}
