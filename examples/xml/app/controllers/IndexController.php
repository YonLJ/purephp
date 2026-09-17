<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/XmlData.php';
require_once __DIR__ . '/../../views/xml.cmp.php';

/**
 * The xml page controller.
 *
 * @return string The rendered document.
 */
function indexController(): string
{
    return (string)xmlPage(xmlData());
}
