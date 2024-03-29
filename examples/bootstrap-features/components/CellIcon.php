<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\p;

require_once __DIR__ . '/Icon.php';

function CellIcon(array $props): HTML
{
    [
        'icon' => $icon,
        'title' => $title,
        'content' => $content
    ] = $props;

    return (
        div(
            Icon($icon)->class('bi text-muted flex-shrink-0 me-3')->width('1.75em')->height('1.75em'),
            div(
                h3($title)->class('fw-bold mb-0 fs-4'),
                p($content)
            )
        )->class('col d-flex align-items-start')
    );
}
