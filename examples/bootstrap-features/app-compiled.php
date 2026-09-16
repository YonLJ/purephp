<?php declare(strict_types=1);

require_once '../../vendor/autoload.php';
require_once './components/Divider.php';
require_once './shapes.php';
require_once './svgs.php';

/**
 * Compiled version of app.php: the shape is built once per process, every
 * request only binds data.
 */
FeaturePageShape()->print(require __DIR__ . '/bindings.php');
