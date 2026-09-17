<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/PricingData.php';
require_once __DIR__ . '/../../views/pricing.cmp.php';

/**
 * The pricing page controller: the page function binds the data through the
 * component functions and the precompiled document skeleton.
 *
 * @return string The rendered document.
 */
function pricingController(): string
{
    return (string)pricingPage(pricingData());
}
