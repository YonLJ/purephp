<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/PricingData.php';
require_once __DIR__ . '/../../views/pricing.cmp.php';

/**
 * The pricing page controller for the plain view: the same bindings through
 * views/pricing.plain.php, which is markup and native PHP, so nothing of
 * purephp is called while it renders. The component functions run here, before
 * the view is loaded.
 *
 * @return string The rendered document.
 */
function plainPricingController(): string
{
    return plain('pricing', pricingBindings(pricingData()));
}
