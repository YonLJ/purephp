<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/XmlData.php';

/**
 * The xml page controller for the artifact: it fills views/xml.pure.php with
 * data through view().
 *
 * @return string The rendered document.
 */
function indexController(): string
{
    return view('xml.pure', xmlData());
}
