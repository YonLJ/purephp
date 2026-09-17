<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\body;
use function Pure\HTML\div;
use function Pure\HTML\head;
use function Pure\HTML\html;
use function Pure\HTML\link;
use function Pure\HTML\meta;
use function Pure\HTML\title;

/**
 * The pricing page skeleton: everything but the four component blocks is
 * static. `pure compile` precompiles it into views/pricing.pure.php.
 */
return Compile::shape(
    html(
        head(
            meta()->http_equiv('Content-Type')->content('text/html; charset=UTF-8'),
            meta()->name('viewport')->content('width=device-width, initial-scale=1, shrink-to-fit=no'),
            meta()->name('description')->content(''),
            meta()->name('author')->content(''),
            link()->rel('icon')->href('https://getbootstrap.com/docs/4.0/assets/img/favicons/favicon.ico'),
            title('Pricing example for Bootstrap'),
            link()->rel('canonical')->href('https://getbootstrap.com/docs/4.0/examples/pricing/'),
            link()->href('https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css')->rel('stylesheet'),
            link()->href('./pricing.css')->rel('stylesheet')
        ),
        body(
            Slot::raw('header'),
            Slot::raw('pricing'),
            div(
                Slot::raw('deck'),
                Slot::raw('footer')
            )->class('container')
        )
    )->lang('en')
);
