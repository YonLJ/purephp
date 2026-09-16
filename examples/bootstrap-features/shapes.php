<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h2;
use function Pure\HTML\h3;
use function Pure\HTML\h4;
use function Pure\HTML\h1;
use function Pure\HTML\img;
use function Pure\HTML\li;
use function Pure\HTML\main;
use function Pure\HTML\p;
use function Pure\HTML\small;
use function Pure\HTML\ul;
use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

/**
 * Shapes are data-free trees: static props come from function arguments,
 * dynamic props are Slot placeholders bound when the shape is rendered.
 *
 * Every function below compiles once per process.
 */
function IconShape(string $class = 'bi', string $width = '1em', string $height = '1em'): Shape
{
    static $shapes = [];
    $key = $class . '|' . $width . '|' . $height;

    return $shapes[$key] ??= Compile::shape(
        svg(svgUse()->href(Slot::attr('href')))->class($class)->width($width)->height($height)
    );
}

function IconColumnShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            div(
                Slot::sub('icon', IconShape(), static fn (array $data): array => ['href' => '#' . $data['icon']])
            )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
            h3(Slot::text('title'))->class('fs-2'),
            p(Slot::text('content')),
            a(
                Slot::text('linkText'),
                svg(svgUse()->href('#chevron-right'))->class('bi')->width('1em')->height('1em')
            )->href(Slot::attr('link'))->class('icon-link d-inline-flex align-items-center')
        )->class('feature col')
    );
}

function HangingIconShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            div(
                Slot::sub('icon', IconShape(), static fn (array $data): array => ['href' => '#' . $data['icon']])
            )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
            div(
                h3(Slot::text('title'))->class('fs-2'),
                p(Slot::text('content')),
                a(Slot::text('linkText'))->href(Slot::attr('link'))->class('btn btn-primary')
            )
        )->class('col d-flex align-items-start')
    );
}

function CustomCardShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            div(
                div(
                    h3(Slot::text('title'))->class('pt-5 mt-5 mb-4 display-6 lh-1 fw-bold'),
                    ul(
                        li(
                            img()->src(Slot::attr('icon'))->alt('Bootstrap')->width('32')->height('32')->class('rounded-circle border border-white')
                        )->class('me-auto'),
                        li(
                            svg(svgUse()->href('#geo-fill'))->class('bi me-2')->width('1em')->height('1em'),
                            small(Slot::text('location'))
                        )->class('d-flex align-items-center me-3'),
                        li(
                            svg(svgUse()->href('#calendar3'))->class('bi me-2')->width('1em')->height('1em'),
                            small(Slot::text('date'))
                        )->class('d-flex align-items-center'),
                    )->class('d-flex list-unstyled mt-auto')
                )->class('d-flex flex-column h-100 p-5 pb-3 text-white text-shadow-1')
            )->class('card card-cover h-100 overflow-hidden text-bg-dark rounded-4 shadow-lg')->style(Slot::attr('style'))
        )->class('col')
    );
}

function CellIconShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            Slot::sub('icon', IconShape('bi text-muted flex-shrink-0 me-3', '1.75em', '1.75em'), static fn (array $data): array => ['href' => '#' . $data['icon']]),
            div(
                h3(Slot::text('title'))->class('fw-bold mb-0 fs-4'),
                p(Slot::text('content'))
            )
        )->class('col d-flex align-items-start')
    );
}

function MainFeatureShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h3(Slot::text('title'))->class('fw-bold'),
            p(Slot::text('content'))->class('text-muted'),
            a(Slot::text('linkText'))->class('btn btn-primary btn-lg')->href(Slot::attr('link'))
        )->class('col d-flex flex-column align-items-start gap-2')
    );
}

function FeatureTitleShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            div(
                Slot::sub('icon', IconShape(), static fn (array $data): array => ['href' => '#' . $data['icon']])
            )->class('feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-4 rounded-3'),
            h4(Slot::text('title'))->class('fw-semibold mb-0'),
            p(Slot::text('content'))->class('text-muted')
        )->class('col d-flex flex-column gap-2')
    );
}

function SectionShape(Shape $item, string $classList): Shape
{
    static $shapes = [];
    $key = $classList . '|' . $item->id();

    return $shapes[$key] ??= Compile::shape(
        div(
            h2(Slot::text('title'))->class('pb-2 border-bottom'),
            div(Slot::each('contents', $item))->class($classList)
        )->class('container px-4 py-5')
    );
}

function FeatureSectionShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h2(Slot::text('title'))->class('pb-2 border-bottom'),
            div(
                Slot::sub('main', MainFeatureShape()),
                div(
                    div(Slot::each('features', FeatureTitleShape()))->class('row row-cols-1 row-cols-sm-2 g-4')
                )->class('col')
            )->class('row row-cols-1 row-cols-md-2 align-items-md-center g-5 py-5')
        )->class('container px-4 py-5')
    );
}

function FeaturePageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        main(
            h1('Features examples')->class('visually-hidden'),
            Slot::sub('columns', SectionShape(IconColumnShape(), 'row g-4 py-5 row-cols-1 row-cols-lg-3')),
            div()->class('b-example-divider'),
            Slot::sub('hanging', SectionShape(HangingIconShape(), 'row g-4 py-5 row-cols-1 row-cols-lg-3')),
            div()->class('b-example-divider'),
            Slot::sub('cards', SectionShape(CustomCardShape(), 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5')),
            div()->class('b-example-divider'),
            Slot::sub('grid', SectionShape(CellIconShape(), 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5')),
            div()->class('b-example-divider'),
            Slot::sub('features', FeatureSectionShape()),
        )
    );
}
