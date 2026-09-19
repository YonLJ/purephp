<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{registerPage, renderPage};
use function Pure\HTML\{body, div, head, html, link, meta, title};

require_once __DIR__ . '/../components/PageHeader.cmp.php';
require_once __DIR__ . '/../components/PricingHeader.cmp.php';
require_once __DIR__ . '/../components/CardDeck.cmp.php';
require_once __DIR__ . '/../components/PageFooter.cmp.php';

registerPage('Pricing', __FILE__, static function (): Shape {
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
});

/**
 * The data of the pricing page: the four rendered blocks. The plain view
 * controller uses the same bindings, so both flavors render one page.
 *
 * @param array<string, mixed> $data The page data from the controller.
 * @return array{header: Raw, pricing: Raw, deck: Raw, footer: Raw}
 */
function pricingBindings(array $data): array
{
    return [
        'header' => PageHeader('Company name', $data['header']['navs'], $data['header']['signUp']),
        'pricing' => PricingHeader($data['pricing']['title'], $data['pricing']['desc']),
        'deck' => CardDeck($data['deck']['cards']),
        'footer' => PageFooter($data['footer']['logo'], $data['footer']['links']),
    ];
}

/**
 * The pricing page: the document skeleton comes from views/pricing.shape.php
 * (precompiled with `pure compile`), the four blocks are composed from the
 * component functions.
 *
 * @param array<string, mixed> $data The page data from the controller.
 */
function pricingPage(array $data): Raw
{
    return renderPage('Pricing', pricingBindings($data));
}
