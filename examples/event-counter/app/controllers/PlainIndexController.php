<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/CounterData.php';

/**
 * The counter page controller for the plain view.
 *
 * @return string The rendered document.
 */
function plainIndexController(): string
{
    return plain('counter', counterData());
}
