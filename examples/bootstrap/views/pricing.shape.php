<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../components/PageHeader.php';
require_once __DIR__ . '/../components/PricingHeader.php';
require_once __DIR__ . '/../components/CardDeck.php';
require_once __DIR__ . '/../components/PageFooter.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\body;
use function Pure\HTML\div;
use function Pure\HTML\head;
use function Pure\HTML\html;
use function Pure\HTML\link;
use function Pure\HTML\meta;
use function Pure\HTML\title;

/**
 * The pricing page as a shape file: `pure compile` precompiles it into
 * views/pricing.pure.php, which app/controllers/PricingController.php renders
 * through view() and PlainPricingController.php through plain().
 */
function PricingPageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
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
                Slot::child('header', PageHeaderShape('Company name')),
                Slot::child('pricing', PricingHeaderShape()),
                div(
                    Slot::child('deck', CardDeckShape()),
                    Slot::child('footer', PageFooterShape())
                )->class('container')
            )
        )->lang('en')
    );
}

return PricingPageShape();
