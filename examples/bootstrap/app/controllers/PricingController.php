<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/PricingData.php';

/**
 * The pricing page controller for the artifact: it fills
 * views/pricing.pure.php
 * with data through view(). Slots are validated, so a required slot the data
 * does not provide throws MissingSlotException.
 *
 * @return string The rendered document.
 */
function pricingController(): string
{
    return view('pricing.pure', pricingData());
}
