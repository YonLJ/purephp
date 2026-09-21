<?php declare(strict_types=1);

/**
 * Front controller and router of the counter page — one entry for the two forms
 * of its view:
 *
 *     /            redirects to /plain
 *     /index.php   redirects to /plain
 *     /pure        the page function and the counter artifact
 *     /plain       the plain view (views/counter.plain.php, no library in the view file)
 *
 * A request that matches nothing gets a 404 that lists every route. Static files
 * stay with the server. Serve it with:
 *
 *     php -S localhost:8000 -t examples/event-counter/public \
 *         examples/event-counter/public/index.php
 */

require_once __DIR__ . '/../app/controllers/IndexController.php';
require_once __DIR__ . '/../app/controllers/PlainIndexController.php';

use function Pure\HTML\a;
use function Pure\HTML\body;
use function Pure\HTML\code;
use function Pure\HTML\h1;
use function Pure\HTML\head;
use function Pure\HTML\html;
use function Pure\HTML\li;
use function Pure\HTML\meta;
use function Pure\HTML\p;
use function Pure\HTML\title;
use function Pure\HTML\ul;

use function Pure\Utils\renderHTML;

// The pages and aliases of this router, for the 404 map below.
$pages = [
    '/pure' => 'the page function and the artifact',
    '/plain' => 'the plain view, no library in the view file',
];
$redirects = [
    '/' => '/plain',
    '/index.php' => '/plain',
];

// A CLI run without a request URL renders the plain view.
$uri = $_SERVER['REQUEST_URI'] ?? '/plain';
$path = parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH) ?: '/';

// Let the built-in server deliver existing files itself.
$file = realpath(__DIR__ . $path);

if (
    PHP_SAPI === 'cli-server'
    && $file !== false
    && is_file($file)
    && $file !== __FILE__
    && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
) {
    return false;
}

header('Content-Type: text/html; charset=utf-8');

switch ($path) {
    case '/':
    case '/index.php':
        header('Location: /plain', true, 302);

        break;
    case '/pure':
        echo indexController();

        break;
    case '/plain':
        echo plainIndexController();

        break;
    default:
        renderNotFound($path, $pages, $redirects);
}

/**
 * The 404 page: it reports the request and lists every route that exists, so it
 * doubles as a map of the example. It is built with the HTML functions of this
 * library, which escape the requested path and the route descriptions.
 *
 * @param string $path The requested path.
 * @param array<string, string> $pages The routes and what they render.
 * @param array<string, string> $redirects The aliases and their targets.
 */
function renderNotFound(string $path, array $pages, array $redirects): void
{
    http_response_code(404);

    $items = [];

    foreach ($pages as $route => $description) {
        $items[] = li(a($route)->href($route), ' — ', $description);
    }

    foreach ($redirects as $route => $target) {
        $items[] = li(a($route)->href($route), ' — redirects to ', code($target));
    }

    echo renderHTML(html(
        head(
            meta()->charset('utf-8'),
            title('404 — nothing at ' . $path)
        ),
        body(
            h1('404'),
            p('Nothing is routed at ', code($path), '. These routes exist:'),
            ul(...$items)
        )
    )->lang('en'));
}
