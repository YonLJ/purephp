<?php declare(strict_types=1);

require_once __DIR__ . '/features-sections.php';

use Pure\Core\Raw;

use function Pure\Component\renderPage;

/**
 * The data of the features page: the title and the rendered body. The plain
 * view controller uses the same bindings, so both flavors render one page.
 *
 * @param array<string, mixed> $data The page data from the controller.
 * @return array{title: string, content: string}
 */
function featuresBindings(array $data): array
{
    return [
        'title' => $data['title'],
        'content' => (string)FeaturesBody($data['content']),
    ];
}

/**
 * The features page: the document skeleton comes from views/features.shape.php
 * (precompiled with `pure compile`), the body is composed from the component
 * functions.
 *
 * @param array<string, mixed> $data The page data from the controller.
 */
function featuresPage(array $data): Raw
{
    return renderPage(__DIR__ . '/features.shape.php', featuresBindings($data));
}
