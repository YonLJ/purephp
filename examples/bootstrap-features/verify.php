<?php declare(strict_types=1);

/**
 * Byte-compares the classic renderer output (app.php) with the compiled
 * renderer output (app-compiled.php), each in its own process.
 *
 * Run from this directory: php verify.php
 */

$php = escapeshellarg(PHP_BINARY);

$plain = (string)shell_exec("{$php} app.php");
$compiled = (string)shell_exec("{$php} app-compiled.php");

if ($plain === '') {
    echo "app.php produced no output\n";

    exit(2);
}

if ($plain === $compiled) {
    echo 'identical: ', strlen($plain), " bytes\n";

    exit(0);
}

$limit = min(strlen($plain), strlen($compiled));
$offset = 0;
while ($offset < $limit && $plain[$offset] === $compiled[$offset]) {
    $offset++;
}

echo "DIFFERENT at byte {$offset} (plain ", strlen($plain), ' bytes, compiled ', strlen($compiled), " bytes)\n";
echo 'plain   : ', substr($plain, max(0, $offset - 100), 220), "\n";
echo 'compiled: ', substr($compiled, max(0, $offset - 100), 220), "\n";

exit(1);
