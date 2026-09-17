<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Pure\Compile\Renderer;

/**
 * The view layer: renders one compiled view with its data.
 *
 * `features.pure` maps to views/features.pure.php, which is loaded once per process.
 * The artifact returns a Renderer whose document header (the `<!DOCTYPE html>`
 * of the root tag) belongs to the response, so a controller only has to return
 * view() and the entry point prints it.
 *
 * @param string $name The view name, without the .php suffix.
 * @param array<string, mixed> $data The view data, keyed by slot name.
 * @return string The rendered document.
 */
function view(string $name, array $data = []): string
{
    static $views = [];

    $renderer = $views[$name] ??= require __DIR__ . '/../views/' . $name . '.php';

    if (!$renderer instanceof Renderer) {
        throw new RuntimeException("view '{$name}' must return a Renderer.");
    }

    return $renderer->header . $renderer->render($data);
}

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
