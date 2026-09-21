<?php declare(strict_types=1);
use Pure\Component\Binds;
use Pure\Component\Call;
use Pure\Component\Component;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{div, h1, p};

require_once __DIR__ . '/../app/services/PricingService.php';

/**
 * The pricing page heading template.
 */
register('PricingHeader', __FILE__,
    factory: static fn () => div(
        h1(Slot::value('title'))->class('display-4'),
        p(Slot::value('desc'))->class('lead')
    )->class('pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center'),
    prepare: #[Binds('title', 'desc')] static function (): array {
        return PricingService::pricing();
    }
);

/**
 * The pricing page heading: it fetches the title and the description from the
 * service. `PricingHeader()`.
 */
#[Component]
function PricingHeader(mixed ...$children): Call
{
    return component('PricingHeader', ...$children);
}
