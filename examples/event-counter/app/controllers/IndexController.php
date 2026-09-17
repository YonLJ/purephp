<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/CounterData.php';

/**
 * The counter page controller for the artifact.
 *
 * @return string The rendered document.
 */
function indexController(): string
{
    return view('counter.pure', counterData());
}
