<?php declare(strict_types=1);

require_once '../../vendor/autoload.php';
require_once './components/Section.php';
require_once './components/Divider.php';
require_once './components/IconColumn.php';
require_once './components/HangingIcon.php';
require_once './components/CustomCard.php';
require_once './components/CellIcon.php';
require_once './components/FeatureTitle.php';
require_once './components/MainFeature.php';
require_once './svgs.php';
require_once './data.php';

use function Tiny\Tags\HTML\div;
use function Tiny\Tags\HTML\h1;
use function Tiny\Tags\HTML\main;

$appView = main(
    h1('Features examples')->class('visually-hidden'),
    Section([
        'title' => 'Columns with icons',
        'contents' => array_map(fn ($data) => IconColumn($data), $columnsData),
        'classList' => 'row g-4 py-5 row-cols-1 row-cols-lg-3'
    ]),
    Divider(),
    Section([
        'title' => 'Hanging icons',
        'contents' => array_map(fn ($data) => HangingIcon($data), $hangingData),
        'classList' => 'row g-4 py-5 row-cols-1 row-cols-lg-3'
    ]),
    Divider(),
    Section([
        'title' => 'Custom cards',
        'contents' => array_map(fn ($data) => CustomCard($data), $cardsData),
        'classList' => 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5'
    ]),
    Divider(),
    Section([
        'title' => 'Icon grid',
        'contents' => array_map(fn ($data) => CellIcon($data), $gridData),
        'classList' => 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5'
    ]),
    Divider(),
    Section([
        'title' => 'Features with title',
        'contents' => [
            MainFeature([
                'title' => 'Left-aligned title explaining these awesome features',
                'content' => "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
                'link' => '#',
                'linkText' => 'Primary button'
            ]),
            div(
                div(
                    ...array_map(fn ($data) => FeatureTitle($data), $featuresData)
                )->class('row row-cols-1 row-cols-sm-2 g-4')
            )->class('col')
        ],
        'classList' => 'row row-cols-1 row-cols-md-2 align-items-md-center g-5 py-5'
    ]),
);

echo $svgsView;
echo $appView;
