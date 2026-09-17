<?php declare(strict_types=1);

/**
 * Classic (uncompiled) component builders for the features page: plain
 * functions that build a tag tree from their arguments, the way a hand-written
 * page does.
 *
 * They build the same markup as the shapes in
 * examples/bootstrap/views/features-sections.php, so the benchmark can compare
 * "build tree + render()" with the compiled path byte for byte.
 *
 * The composer autoloader must be loaded before these functions are called.
 */

use Pure\Core\HTML;
use Pure\Core\SVG;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h2;
use function Pure\HTML\h3;
use function Pure\HTML\h4;
use function Pure\HTML\img;
use function Pure\HTML\li;
use function Pure\HTML\p;
use function Pure\HTML\small;
use function Pure\HTML\ul;

use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

/**
 * One icon, by symbol id.
 */
function Icon(string $icon): SVG
{
    return svg(svgUse()->href("#{$icon}"));
}

/**
 * One page section: a titled container around a row of items.
 *
 * @param list<HTML> $contents
 */
function Section(string $title, array $contents, string $classList): HTML
{
    return (
        div(
            h2($title)->class('pb-2 border-bottom'),
            div(...$contents)->class($classList)
        )->class('container px-4 py-5')
    );
}

/**
 * A section separator.
 */
function Divider(): HTML
{
    return div()->class('b-example-divider');
}

/**
 * @param array<string, string> $data
 */
function IconColumn(array $data): HTML
{
    return (
        div(
            div(
                Icon($data['icon'])->class('bi')->width('1em')->height('1em')
            )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
            h3($data['title'])->class('fs-2'),
            p($data['content']),
            a(
                $data['linkText'],
                Icon('chevron-right')->class('bi')->width('1em')->height('1em'),
            )->href($data['link'])->class('icon-link d-inline-flex align-items-center')
        )->class('feature col')
    );
}

/**
 * @param array<string, string> $data
 */
function HangingIcon(array $data): HTML
{
    return (
        div(
            div(
                Icon($data['icon'])->class('bi')->width('1em')->height('1em')
            )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
            div(
                h3($data['title'])->class('fs-2'),
                p($data['content']),
                a($data['linkText'])->href($data['link'])->class('btn btn-primary')
            )
        )->class('col d-flex align-items-start')
    );
}

/**
 * @param array<string, string> $data
 */
function CustomCard(array $data): HTML
{
    return (
        div(
            div(
                div(
                    h3($data['title'])->class('pt-5 mt-5 mb-4 display-6 lh-1 fw-bold'),
                    ul(
                        li(
                            img()->src($data['icon'])->alt('Bootstrap')->width('32')->height('32')->class('rounded-circle border border-white')
                        )->class('me-auto'),
                        li(
                            Icon('geo-fill')->class('bi me-2')->width('1em')->height('1em'),
                            small($data['location'])
                        )->class('d-flex align-items-center me-3'),
                        li(
                            Icon('calendar3')->class('bi me-2')->width('1em')->height('1em'),
                            small($data['date'])
                        )->class('d-flex align-items-center'),
                    )->class('d-flex list-unstyled mt-auto')
                )->class('d-flex flex-column h-100 p-5 pb-3 text-white text-shadow-1')
            )->class('card card-cover h-100 overflow-hidden text-bg-dark rounded-4 shadow-lg')
                ->style("background-image: url('{$data['bgImg']}');")
        )->class('col')
    );
}

/**
 * @param array<string, string> $data
 */
function CellIcon(array $data): HTML
{
    return (
        div(
            Icon($data['icon'])->class('bi text-muted flex-shrink-0 me-3')->width('1.75em')->height('1.75em'),
            div(
                h3($data['title'])->class('fw-bold mb-0 fs-4'),
                p($data['content'])
            )
        )->class('col d-flex align-items-start')
    );
}

/**
 * @param array<string, string> $data
 */
function FeatureTitle(array $data): HTML
{
    return (
        div(
            div(
                Icon($data['icon'])->class('bi')->width('1em')->height('1em')
            )->class('feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-4 rounded-3'),
            h4($data['title'])->class('fw-semibold mb-0'),
            p($data['content'])->class('text-muted')
        )->class('col d-flex flex-column gap-2')
    );
}

/**
 * The text column of the last section.
 */
function MainFeature(string $title, string $content, string $link, string $linkText): HTML
{
    return (
        div(
            h3($title)->class('fw-bold'),
            p($content)->class('text-muted'),
            a($linkText)->class('btn btn-primary btn-lg')->href($link)
        )->class('col d-flex flex-column align-items-start gap-2')
    );
}
