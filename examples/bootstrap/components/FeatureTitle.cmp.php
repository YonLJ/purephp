<?php declare(strict_types=1);
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{div, h4, p};

require_once __DIR__ . '/Icon.cmp.php';

/**
 * One "features with title" item template.
 */
register('FeatureTitle', __FILE__,
    factory: static fn () => div(
        div(
            Slot::raw('icon')
        )->class('feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-4 rounded-3'),
        h4(Slot::value('title'))->class('fw-semibold mb-0'),
        p(Slot::value('content'))->class('text-muted')
    )->class('col d-flex flex-column gap-2'),
    prepare: static function (string $icon, string $title, string $content): array {
        return [
            'icon' => Icon()->href('#' . $icon),
            'title' => $title,
            'content' => $content,
        ];
    }
);

/**
 * One "features with title" item: `FeatureTitle()->icon('bootstrap')->title(...)->content(...)`.
 */
function FeatureTitle(mixed ...$children): Call
{
    return component('FeatureTitle', ...$children);
}
