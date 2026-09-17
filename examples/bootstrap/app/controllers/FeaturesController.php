<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/FeaturesData.php';
require_once __DIR__ . '/../../views/features.cmp.php';

/**
 * The features page controller: the page function binds the data through the
 * component functions and the precompiled document skeleton. Slots are
 * validated, so a required slot the data does not provide throws
 * MissingSlotException.
 *
 * @return string The rendered document.
 */
function featuresController(): string
{
    return (string)featuresPage(featuresData());
}
