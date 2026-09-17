<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{button, div, h1, h4, li, small, ul};

/**
 * One pricing card template.
 */
register('Card', __FILE__, static fn (): Shape => Compile::shape(
    div(
        div(
            h4(Slot::text('type'))->class('my-0 font-weight-normal')
        )->class('card-header'),
        div(
            h1('$', Slot::text('price'), ' ', small('/ mo')->class('text-muted'))->class('card-title pricing-card-title'),
            ul(Slot::each('features', li(Slot::text('value'))))->class('list-unstyled mt-3 mb-4'),
            button(Slot::text('text'))->type('button')->class(Slot::attr('class'))
        )->class('card-body')
    )->class('card mb-4 box-shadow')
));

/**
 * One pricing card: the plan name, its price, the feature bullets and the
 * button label with its class list.
 *
 * @param list<string> $features
 */
function Card(string $type, string $price, array $features, string $text, string $class): Raw
{
    $items = [];

    foreach ($features as $feature) {
        $items[] = ['value' => $feature];
    }

    return render(
        'Card',
        type: $type,
        price: $price,
        features: $items,
        text: $text,
        class: $class,
    );
}
