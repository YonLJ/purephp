<!DOCTYPE html>
<?php
require '../../vendor/autoload.php';

use Tiny\VDom;
use function Tiny\a;
use function Tiny\body;
use function Tiny\div;
use function Tiny\footer;
use function Tiny\h1;
use function Tiny\h3;
use function Tiny\head;
use function Tiny\header;
use function Tiny\html;
use function Tiny\nav;
use function Tiny\main;
use function Tiny\meta;
use function Tiny\p;
use function Tiny\title;
use function Tiny\link;
use function Tiny\style;

$view = (
    html(
        head(
            meta()->charset('utf-8'),
            meta()->name('viewport')->content('width=device-width, initial-scale=1'),
            meta()->name('description')->content('tiny demo'),
            meta()->name('author')->content('Mark Otto, Jacob Thornton, and Bootstrap contributors'),
            meta()->name('generator')->content('Hugo 0.108.0'),
            title('Cover Template · Bootstrap v5.3'),
            link()->rel('canonical')->href('https://getbootstrap.com/docs/5.3/examples/cover/'),
            link()->href('https://getbootstrap.com/docs/5.3/dist/css/bootstrap.min.css')->rel('stylesheet')->integrity('sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD')->crossorigin('anonymous'),
            link()->rel('apple-touch-icon')->href('https://getbootstrap.com/docs/5.3/assets/img/favicons/apple-touch-icon.png')->sizes('180x180'),
            link()->rel('icon')->href('https://getbootstrap.com/docs/5.3/assets/img/favicons/favicon-32x32.png')->sizes('32x32')->type('image/png'),
            link()->rel('icon')->href('https://getbootstrap.com/docs/5.3/assets/img/favicons/favicon-16x16.png')->sizes('16x16')->type('image/png'),
            link()->rel('manifest')->href('https://getbootstrap.com/docs/5.3/assets/img/favicons/manifest.json'),
            link()->rel('mask-icon')->href('https://getbootstrap.com/docs/5.3/assets/img/favicons/safari-pinned-tab.svg')->color('#712cf9'),
            link()->rel('icon')->href('https://getbootstrap.com/docs/5.3/assets/img/favicons/favicon.ico'),
            meta()->name('theme-color')->content('#712cf9'),
            style(<<<END
            .bd-placeholder-img {
                font-size: 1.125rem;
                text-anchor: middle;
                -webkit-user-select: none;
                -moz-user-select: none;
                user-select: none;
            }

            @media (min-width: 768px) {
                .bd-placeholder-img-lg {
                    font-size: 3.5rem;
                }
            }

            .b-example-divider {
                height: 3rem;
                background-color: rgba(0, 0, 0, .1);
                border: solid rgba(0, 0, 0, .15);
                border-width: 1px 0;
                box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
            }

            .b-example-vr {
                flex-shrink: 0;
                width: 1.5rem;
                height: 100vh;
            }

            .bi {
                vertical-align: -.125em;
                fill: currentColor;
            }

            .nav-scroller {
                position: relative;
                z-index: 2;
                height: 2.75rem;
                overflow-y: hidden;
            }

            .nav-scroller .nav {
                display: flex;
                flex-wrap: nowrap;
                padding-bottom: 1rem;
                margin-top: -1px;
                overflow-x: auto;
                text-align: center;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            END),
            link()->href('https://getbootstrap.com/docs/5.3/examples/cover/cover.css')->rel('stylesheet')
        ),
        body(
            div(
                header(
                    div(
                        h3('Cover')->class('float-md-start mb-0'),
                        nav(
                            a('Home')->class('nav-link fw-bold py-1 px-0 active')->aria_current('page')->href('#'),
                            a('Features')->class('nav-link fw-bold py-1 px-0')->href('#'),
                            a('Contact')->class('nav-link fw-bold py-1 px-0')->href('#')
                        )->class('nav nav-masthead justify-content-center float-md-end')
                    )
                )->class('mb-auto'),
                main(
                    h1('Cover your page.'),
                    p('Cover is a one-page template for building simple and beautiful home pages. Download, edit the text, and add your own fullscreen background photo to make it your own.')->class('lead'),
                    p(
                        a('Learn more')->class('btn btn-lg btn-light fw-bold border-white bg-white')->href('#')
                    )->class('lead')
                )->class('px-3'),
                footer(
                    p(
                        'Cover template for',
                        a('Bootstrap')->class('text-white')->href('https://getbootstrap.com/'),
                        ', by',
                        a('@mdo')->class('text-white')->href('https://twitter.com/mdo'),
                        '.'
                    )
                )->class('mt-auto text-white-50')
            )->class('cover-container d-flex w-100 h-100 p-3 mx-auto flex-column')
        )->class('d-flex h-100 text-center text-bg-dark')
    )->lang('en')->class('h-100')
);

$vDom = new VDom();
$vDom->outputHTML($view);
