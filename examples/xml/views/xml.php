<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\renderPage;

/**
 * The xml page: the document comes from views/xml.shape.php (precompiled with
 * `pure compile`).
 *
 * @param array<string, mixed> $data The page data from the controller.
 */
function xmlPage(array $data): Raw
{
    return renderPage(__DIR__ . '/xml.shape.php', ['addresses' => $data['addresses']]);
}
