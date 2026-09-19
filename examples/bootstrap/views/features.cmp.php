<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{registerPage, renderPage};
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

registerPage('Features', __FILE__, static function (): Shape {
    /**
     * The features page skeleton: the head and the SVG symbol sheet are static, the
     * title and the rendered body come from the page function. `pure compile`
     * precompiles it into views/features.pure.php.
     */
    $site = 'https://getbootstrap.com/docs/5.2';
    $favicons = $site . '/assets/img/favicons';

    return Compile::shape(
        html(
            head(
                meta()->charset('utf-8'),
                meta()->name('viewport')->content('width=device-width, initial-scale=1'),
                meta()->name('description')->content('pure demo'),
                meta()->name('author')->content('Mark Otto, Jacob Thornton, and Bootstrap contributors'),
                meta()->name('generator')->content('Hugo 0.104.2'),
                meta()->name('theme-color')->content('#712cf9'),
                title(Slot::text('title')),
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
                IconSheet(),
                Slot::raw('content')
            )
        )
    );
});

/**
 * The data of the features page: the title and the rendered body. The plain
 * view controller uses the same bindings, so both flavors render one page.
 *
 * @param array<string, mixed> $data The page data from the controller.
 * @return array{title: string, content: Raw}
 */
function featuresBindings(array $data): array
{
    return [
        'title' => $data['title'],
        'content' => FeaturesBody($data['content']),
    ];
}

/**
 * The features page: the document skeleton is the registered template
 * (precompiled with `pure compile`), the body is composed from the component
 * functions.
 *
 * @param array<string, mixed> $data The page data from the controller.
 */
function featuresPage(array $data): Raw
{
    return renderPage('Features', featuresBindings($data));
}

/**
 * The body of the features page: the sections are composed from the component
 * functions, each section item rendered by its own function.
 *
 * @param array<string, mixed> $content The page content.
 */
function FeaturesBody(array $content): Raw
{
    return Raw::of(main(
        h1('Features examples')->class('visually-hidden'),
        Section($content['columns']['title'], Raw::of(renderItems($content['columns']['contents'], IconColumn(...))), 'row g-4 py-5 row-cols-1 row-cols-lg-3'),
        Divider(),
        Section($content['hanging']['title'], Raw::of(renderItems($content['hanging']['contents'], HangingIcon(...))), 'row g-4 py-5 row-cols-1 row-cols-lg-3'),
        Divider(),
        Section($content['cards']['title'], Raw::of(renderItems($content['cards']['contents'], CustomCard(...))), 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5'),
        Divider(),
        Section($content['grid']['title'], Raw::of(renderItems($content['grid']['contents'], CellIcon(...))), 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5'),
        Divider(),
        FeatureSection($content['features']['title'], $content['features']['main'], $content['features']['features']),
    )->render());
}

/**
 * Render one list of section items with its component function.
 *
 * @param list<array<string, string>> $items
 * @param callable(array<string, string>): Raw $component
 */
function renderItems(array $items, callable $component): string
{
    $html = '';

    foreach ($items as $item) {
        $html .= $component($item);
    }

    return $html;
}
