<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../views/features.cmp.php';

/**
 * The features page controller: the page function composes the component tree
 * from the precompiled document skeleton; every component fetches its own
 * records from FeaturesService. Slots are validated, so a required slot a
 * component does not provide throws MissingSlotException.
 *
 * @return string The rendered document.
 */
function featuresController(): string
{
    return featuresPage();
}
