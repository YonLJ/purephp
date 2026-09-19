<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2};

require_once __DIR__ . '/../app/services/FeaturesService.php';

/**
 * One page section template: a heading and a grid of already rendered items.
 */
register('Section', __FILE__, static fn () =>
    div(
        h2(Slot::value('title'))->class('pb-2 border-bottom'),
        div(Slot::raw('contents'))->class(Slot::value('class'))
    )->class('container px-4 py-5')
);

/**
 * One page section: it fetches its own title and item records from the
 * service, renders every item with the component the page passes, and hands
 * the result to the template. The raw slot joins the list.
 *
 * @param string $section The section key in FeaturesService.
 * @param callable(array<string, string>): string $item Renders one item record.
 */
function Section(string $section, string $class, callable $item): string
{
    $data = FeaturesService::section($section);

    return render(
        'Section',
        title: $data['title'],
        contents: array_map(static fn (array $record): string => $item(...$record), $data['items']),
        class: $class,
    );
}
