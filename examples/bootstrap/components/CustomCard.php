<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * One "custom cards" item: the cover image is the card background, the icon is
 * the avatar.
 *
 * @param array{title: string, icon: string, location: string, date: string, bgImg: string} $card
 */
function CustomCard(array $card): Raw
{
    return render(
        __DIR__ . '/CustomCard.shape.php',
        title: $card['title'],
        icon: $card['icon'],
        location: $card['location'],
        date: $card['date'],
        style: "background-image: url('{$card['bgImg']}');",
    );
}
