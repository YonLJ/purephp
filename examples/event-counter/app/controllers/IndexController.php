<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/CounterData.php';
require_once __DIR__ . '/../../views/counter.cmp.php';

/**
 * The counter page controller.
 *
 * @return string The rendered document.
 */
function indexController(): string
{
    return (string)counterPage(counterData());
}
