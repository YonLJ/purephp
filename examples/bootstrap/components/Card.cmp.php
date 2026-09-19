<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{button, div, h1, h4, li, small, ul};

/**
 * One pricing card template.
 */
register('Card', __FILE__, static fn () =>
    div(
        div(
            h4(Slot::value('type'))->class('my-0 font-weight-normal')
        )->class('card-header'),
        div(
            h1('$', Slot::value('price'), ' ', small('/ mo')->class('text-muted'))->class('card-title pricing-card-title'),
            ul(Slot::each('features', li(Slot::value('value'))))->class('list-unstyled mt-3 mb-4'),
            button(Slot::value('text'))->type('button')->class(Slot::value('class'))
        )->class('card-body')
    )->class('card mb-4 box-shadow')
);

/**
 * One pricing card: the plan name, its price, the feature bullets and the
 * button label with its class list.
 *
 * @param list<string> $features
 */
function Card(string $type, string $price, array $features, string $text, string $class): string
{
    return render(
        'Card',
        type: $type,
        price: $price,
        features: array_map(static fn (string $feature): array => ['value' => $feature], $features),
        text: $text,
        class: $class,
    );
}
