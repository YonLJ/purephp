<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

/**
 * The loader of a plain view (compiled with `pure compile --plain`): markup and
 * native PHP, with the view data extracted into locals. Nothing of purephp is
 * called here, so this is what a deployment without the library ships.
 *
 * @param string $name The view name, without the .plain.php suffix.
 * @param array<string, mixed> $data The view data, keyed by slot name.
 * @return string The rendered document, including its document header.
 */
function plain(string $name, array $data = []): string
{
    $file = __DIR__ . '/../views/' . $name . '.plain.php';

    if (!is_file($file)) {
        throw new RuntimeException("plain view '{$name}' is missing: run `pure compile --plain`.");
    }

    ob_start();
    extract($data, EXTR_SKIP);
    require $file;

    return (string)ob_get_clean();
}
