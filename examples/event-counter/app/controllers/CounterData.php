<?php declare(strict_types=1);

/**
 * The view data of the counter page.
 *
 * @return array<string, mixed>
 */
function counterData(): array
{
    return [
        'initial' => (string)random_int(0, 100),
    ];
}
