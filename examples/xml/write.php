<?php declare(strict_types=1);

/**
 * CLI entry: renders the shape and saves example.xml next to it.
 *
 *     php examples/xml/write.php
 *
 * The same shape is served by public/index.php at /pure and /plain.
 */

require_once __DIR__ . '/app/controllers/IndexController.php';

$output = indexController();

$file = __DIR__ . '/example.xml';
file_put_contents($file, $output);

echo "wrote: $file\n";
echo strlen($output) . " bytes\n";
