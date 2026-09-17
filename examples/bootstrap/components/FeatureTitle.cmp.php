<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h4, p};

require_once __DIR__ . '/Icon.cmp.php';

/**
 * One "features with title" item template.
 */
register('FeatureTitle', __FILE__, static fn (): Shape => Compile::shape(
    div(
        div(
            Slot::raw('icon')
        )->class('feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-4 rounded-3'),
        h4(Slot::text('title'))->class('fw-semibold mb-0'),
        p(Slot::text('content'))->class('text-muted')
    )->class('col d-flex flex-column gap-2')
));

/**
 * One "features with title" item.
 *
 * @param array{icon: string, title: string, content: string} $item
 */
function FeatureTitle(array $item): Raw
{
    return render(
        'FeatureTitle',
        icon: (string)Icon('#' . $item['icon']),
        title: $item['title'],
        content: $item['content'],
    );
}
