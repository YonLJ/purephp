<?php declare(strict_types=1);
use Pure\Component\Call;
use Pure\Component\Component;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\{a, div, h3, p};

/**
 * The "features with title" main column template.
 */
register('MainFeature', __FILE__, static fn () =>
    div(
        h3(Slot::value('title'))->class('fw-bold'),
        p(Slot::value('content'))->class('text-muted'),
        a(Slot::value('linkText'))->class('btn btn-primary btn-lg')->href(Slot::value('link'))
    )->class('col d-flex flex-column align-items-start gap-2')
);

/**
 * The "features with title" main column:
 * `MainFeature()->title(...)->content(...)->link(...)->linkText(...)`.
 */
#[Component]
function MainFeature(mixed ...$children): Call
{
    return component('MainFeature', ...$children);
}
