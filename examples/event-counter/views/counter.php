<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\renderPage;

/**
 * The counter page: the document skeleton comes from views/counter.shape.php
 * (precompiled with `pure compile`).
 *
 * @param array<string, mixed> $data The page data from the controller.
 */
function counterPage(array $data): Raw
{
    return renderPage(__DIR__ . '/counter.shape.php', ['initial' => $data['initial']]);
}
