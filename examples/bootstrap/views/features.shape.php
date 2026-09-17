<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/features-svgs.php';

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\body;
use function Pure\HTML\head;
use function Pure\HTML\html;
use function Pure\HTML\link;
use function Pure\HTML\meta;
use function Pure\HTML\title;

/**
 * The features page skeleton: the head and the SVG symbol sheet are static, the
 * title and the rendered body come from the page function. `pure compile`
 * precompiles it into views/features.pure.php.
 */
$site = 'https://getbootstrap.com/docs/5.2';
$favicons = $site . '/assets/img/favicons';

return Compile::shape(
    html(
        head(
            meta()->charset('utf-8'),
            meta()->name('viewport')->content('width=device-width, initial-scale=1'),
            meta()->name('description')->content('pure demo'),
            meta()->name('author')->content('Mark Otto, Jacob Thornton, and Bootstrap contributors'),
            meta()->name('generator')->content('Hugo 0.104.2'),
            meta()->name('theme-color')->content('#712cf9'),
            title(Slot::text('title')),
            link()->rel('canonical')->href($site . '/examples/features/'),
            link()->rel('stylesheet')->crossorigin('anonymous')->href($site . '/dist/css/bootstrap.min.css')->integrity('sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65'),
            link()->rel('apple-touch-icon')->sizes('180x180')->href($favicons . '/apple-touch-icon.png'),
            link()->rel('icon')->type('image/png')->sizes('32x32')->href($favicons . '/favicon-32x32.png'),
            link()->rel('icon')->type('image/png')->sizes('16x16')->href($favicons . '/favicon-16x16.png'),
            link()->rel('manifest')->href($favicons . '/manifest.json'),
            link()->rel('mask-icon')->color('#712cf9')->href($favicons . '/safari-pinned-tab.svg'),
            link()->rel('icon')->href($favicons . '/favicon.ico'),
            link()->rel('stylesheet')->href($site . '/examples/features/features.css'),
            link()->rel('stylesheet')->href('./style.css')
        ),
        body(
            IconSheet(),
            Slot::raw('content')
        )
    )
);
