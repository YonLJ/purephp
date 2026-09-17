<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/FeaturesData.php';
require_once __DIR__ . '/../../views/features.cmp.php';

/**
 * The features page controller for the plain view: the same bindings through
 * views/features.plain.php, which is markup and native PHP, so nothing of
 * purephp is called while it renders. The component functions run here, before
 * the view is loaded.
 *
 * @return string The rendered document.
 */
function plainFeaturesController(): string
{
    return plain('features', featuresBindings(featuresData()));
}
