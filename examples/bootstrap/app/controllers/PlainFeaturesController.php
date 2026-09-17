<?php declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/FeaturesData.php';

/**
 * The features page controller for the plain view: the same data through
 * views/features.plain.php, which is markup and native PHP, so nothing of
 * purephp is called while it renders. The strict semantics stay with the
 * artifact: a missing slot is an undefined variable, a null attribute prints
 * empty.
 *
 * @return string The rendered document.
 */
function plainFeaturesController(): string
{
    return plain('features', featuresData());
}
