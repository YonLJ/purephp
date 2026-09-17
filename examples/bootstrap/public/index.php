<?php declare(strict_types=1);

/**
 * Front controller and router of the bootstrap example — one entry, five routes:
 *
 *     /cover             the cover page (static markup, no compile step)
 *     /pure/features     the features page function and component artifacts
 *     /plain/features    the features plain view, components rendered up front
 *     /pure/pricing      the pricing page function and component artifacts
 *     /plain/pricing     the pricing plain view, components rendered up front
 *
 * A request that matches nothing gets a 404 that lists these routes. Static files
 * (style.css, pricing.css) are handed back to the server. Serve it with:
 *
 *     php -S localhost:8000 -t examples/bootstrap/public \
 *         examples/bootstrap/public/index.php
 */

require_once __DIR__ . '/../app/controllers/CoverController.php';
require_once __DIR__ . '/../app/controllers/FeaturesController.php';
require_once __DIR__ . '/../app/controllers/PlainFeaturesController.php';
require_once __DIR__ . '/../app/controllers/PlainPricingController.php';
require_once __DIR__ . '/../app/controllers/PricingController.php';

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

// The routes of this router, for the 404 map below.
$pages = [
    '/cover' => 'the cover page: static markup, no compile step',
    '/pure/features' => 'features: the page function and the component artifacts',
    '/plain/features' => 'features: the plain view, components rendered up front',
    '/pure/pricing' => 'pricing: the page function and the component artifacts',
    '/plain/pricing' => 'pricing: the plain view, components rendered up front',
];

// A CLI run without a request URL renders the plain features view.
$uri = $_SERVER['REQUEST_URI'] ?? '/plain/features';
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
    case '/cover':
        echo coverController();

        break;
    case '/pure/features':
        echo featuresController();

        break;
    case '/plain/features':
        echo plainFeaturesController();

        break;
    case '/pure/pricing':
        echo pricingController();

        break;
    case '/plain/pricing':
        echo plainPricingController();

        break;
    default:
        renderNotFound($path, $pages);
}

/**
 * The 404 page: it reports the request and lists every route that exists, so it
 * doubles as a map of the example. It is built with the HTML functions of this
 * library, which escape the requested path and the route descriptions.
 *
 * @param string $path The requested path.
 * @param array<string, string> $pages The routes and what they render.
 */
function renderNotFound(string $path, array $pages): void
{
    http_response_code(404);

    $items = [];

    foreach ($pages as $route => $description) {
        $items[] = li(a($route)->href($route), ' — ', $description);
    }

    echo '<!DOCTYPE html>', html(
        head(
            meta()->charset('utf-8'),
            title('404 — nothing at ' . $path)
        ),
        body(
            h1('404'),
            p('Nothing is routed at ', code($path), '. These routes exist:'),
            ul(...$items)
        )
    )->lang('en');
}
