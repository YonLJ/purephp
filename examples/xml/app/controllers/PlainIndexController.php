<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/XmlData.php';

/**
 * The xml page controller for the plain view: the same data through
 * views/xml.plain.php, which is markup and native PHP, so nothing of purephp
 * is called while it renders.
 *
 * @return string The rendered document.
 */
function plainIndexController(): string
{
    return plain('xml', xmlData());
}
