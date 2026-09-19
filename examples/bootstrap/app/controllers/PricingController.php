<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../views/pricing.cmp.php';

/**
 * The pricing page controller: the page function composes the component tree
 * from the precompiled document skeleton; every component fetches its own
 * records from PricingService.
 *
 * @return string The rendered document.
 */
function pricingController(): string
{
    return pricingPage();
}
