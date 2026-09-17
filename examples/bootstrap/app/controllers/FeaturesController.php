<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/FeaturesData.php';

/**
 * The features page controller for the artifact: it fills
 * views/features.pure.php with data through view(). Slots are validated, so a
 * required slot the data does not provide throws MissingSlotException.
 *
 * @return string The rendered document.
 */
function featuresController(): string
{
    return view('features.pure', featuresData());
}
