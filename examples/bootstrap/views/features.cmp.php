<?php declare(strict_types=1);


use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{body, h1, head, html, link, main, meta, title};

require_once __DIR__ . '/features-svgs.php';
require_once __DIR__ . '/../components/IconColumn.cmp.php';
require_once __DIR__ . '/../components/HangingIcon.cmp.php';
require_once __DIR__ . '/../components/CustomCard.cmp.php';
require_once __DIR__ . '/../components/CellIcon.cmp.php';
require_once __DIR__ . '/../components/MainFeature.cmp.php';
require_once __DIR__ . '/../components/FeatureTitle.cmp.php';
require_once __DIR__ . '/../components/Section.cmp.php';
require_once __DIR__ . '/../components/Divider.cmp.php';
require_once __DIR__ . '/../components/FeatureSection.cmp.php';

register('Features', __FILE__, static function () {
    /**
     * The features page skeleton: the head and the SVG symbol sheet are static,
     * the body is composed from raw slots populated by featuresBindings().
     * `pure compile` precompiles it into views/features.pure.php.
     */
    $site = 'https://getbootstrap.com/docs/5.2';
    $favicons = $site . '/assets/img/favicons';

    return (
        html(
            head(
                meta()->charset('utf-8'),
                meta()->name('viewport')->content('width=device-width, initial-scale=1'),
                meta()->name('description')->content('pure demo'),
                meta()->name('author')->content('Mark Otto, Jacob Thornton, and Bootstrap contributors'),
                meta()->name('generator')->content('Hugo 0.104.2'),
                meta()->name('theme-color')->content('#712cf9'),
                title(Slot::value('title')),
                link()->rel('canonical')->href($site . '/examples/features/'),
                link()->rel('stylesheet')->crossorigin('anonymous')->href($site . '/dist/css/bootstrap.min.css')->integrity('sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65'),
                link()->rel('apple-touch-icon')->sizes('180x180')->href($favicons . '/apple-touch-icon.png'),
                link()->rel('icon')->type('image/png')->sizes('32x32')->href($favicons . '/favicon-32x32.png'),
                link()->rel('icon')->type('image/png')->sizes('16x16')->href($favicons . '/favicon-16x16.png'),
                link()->rel('manifest')->href($favicons . '/manifest.json'),
                link()->rel('mask-icon')->color('#712cf9')->href($favicons . '/safari-pinned-tab.svg'),
                link()->rel('icon')->href($favicons . '/favicon.ico'),
                link()->rel('stylesheet')->href($site . '/examples/features/features.css'),
                link()->rel('stylesheet')->href('./style.css')
            ),
            body(
                Raw::of(IconSheet()),
                main(
                    h1('Features examples')->class('visually-hidden'),
                    Slot::raw('columns'),
                    Slot::raw('hanging'),
                    Slot::raw('cards'),
                    Slot::raw('grid'),
                    Slot::raw('features')
                )
            )
        )
    );
});

/**
 * The data of the features page: the title and rendered markup for each
 * section. The plain view controller uses the same bindings, so both flavors
 * render one page.
 *
 * @param array<string, mixed> $data The page data from the controller.
 * @return array{title: string, columns: string, hanging: string, cards: string, grid: string, features: string}
 */
function featuresBindings(array $data): array
{
    $content = $data['content'];

    return [
        'title' => $data['title'],
        'columns' => Section($content['columns']['title'], renderItems($content['columns']['contents'], IconColumn(...)), 'row g-4 py-5 row-cols-1 row-cols-lg-3'),
        'hanging' => Divider() . Section($content['hanging']['title'], renderItems($content['hanging']['contents'], HangingIcon(...)), 'row g-4 py-5 row-cols-1 row-cols-lg-3'),
        'cards' => Divider() . Section($content['cards']['title'], renderItems($content['cards']['contents'], CustomCard(...)), 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5'),
        'grid' => Divider() . Section($content['grid']['title'], renderItems($content['grid']['contents'], CellIcon(...)), 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5'),
        'features' => Divider() . FeatureSection($content['features']['title'], $content['features']['main'], $content['features']['features']),
    ];
}

/**
 * The features page: the document skeleton is the registered template
 * (precompiled with `pure compile`), the body is composed from the component
 * functions. The document header is not part of the tree, so it is prepended
 * manually.
 *
 * @param array<string, mixed> $data The page data from the controller.
 */
function featuresPage(array $data): string
{
    return '<!DOCTYPE html>' . render('Features', ...featuresBindings($data));
}

/**
 * Render one list of section items with its component function.
 *
 * @param list<array<string, string>> $items
 * @param callable(array<string, string>): string $component
 */
function renderItems(array $items, callable $component): string
{
    $html = '';

    foreach ($items as $item) {
        $html .= $component($item);
    }

    return $html;
}
