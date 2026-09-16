<?php declare(strict_types=1);

require_once '../../vendor/autoload.php';
require_once './shapes.php';
require_once './svgs.php';

/**
 * The compiled page: the shape is built once per process, every request only
 * binds data.
 */
FeaturePageShape()->print(require __DIR__ . '/bindings.php');
