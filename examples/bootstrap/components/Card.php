<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

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
        __DIR__ . '/Card.shape.php',
        type: $type,
        price: $price,
        features: $items,
        text: $text,
        class: $class,
    );
}
