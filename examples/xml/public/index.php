<?php declare(strict_types=1);

/**
 * Front controller and router of the xml example — one entry for the two forms
 * of its view:
 *
 *     /            redirects to /plain
 *     /index.php   redirects to /plain
 *     /pure        the page function and the xml artifact
 *     /plain       the plain view (views/xml.plain.php, no library in the view file)
 *
 * The response is XML, so the content type is application/xml and a request that
 * matches nothing gets a 404 that lists the routes as XML. The CLI entry that
 * writes example.xml lives next to the example in write.php; serve this router
 * with:
 *
 *     php -S localhost:8000 -t examples/xml/public examples/xml/public/index.php
 */

require_once __DIR__ . '/../app/controllers/IndexController.php';
require_once __DIR__ . '/../app/controllers/PlainIndexController.php';

use Pure\Core\XML;

use function Pure\Utils\renderXML;

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

header('Content-Type: application/xml; charset=utf-8');

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
 * The 404 document: it reports the request and lists every route that exists, so
 * it doubles as a map of the example. It is built with XML tags, which escape
 * the requested path and the route descriptions.
 *
 * @param string $path The requested path.
 * @param array<string, string> $pages The routes and what they render.
 * @param array<string, string> $redirects The aliases and their targets.
 */
function renderNotFound(string $path, array $pages, array $redirects): void
{
    http_response_code(404);

    $routes = [];

    foreach ($pages as $route => $description) {
        $routes[] = XML::route($description)->path($route);
    }

    foreach ($redirects as $route => $target) {
        $routes[] = XML::route()->path($route)->redirects_to($target);
    }

    echo renderXML(XML::routes(...$routes)->request($path)), "\n";
}
