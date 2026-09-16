<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\div;
use function Pure\HTML\h4;
use function Pure\HTML\p;

require_once __DIR__ . '/Icon.php';

function FeatureTitle(array $props): HTML
{
    [
        'icon' => $icon,
        'title' => $title,
        'content' => $content
    ] = $props;

    return (
        div(
            div(
                Icon($icon)->class('bi')->width('1em')->height('1em')
            )->class('feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-4 rounded-3'),
            h4($title)->class('fw-semibold mb-0'),
            p($content)->class('text-muted')
        )->class('col d-flex flex-column gap-2')
    );
}
