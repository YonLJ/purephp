<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/XmlData.php';

/**
 * The xml page controller for the plain view.
 *
 * @return string The rendered document.
 */
function plainIndexController(): string
{
    $data = xmlData();

    return plain('xml', ['addresses' => $data['addresses']]);
}
