<?php declare(strict_types=1);
require '../../vendor/autoload.php';

use function Tiny\Html\button;
use function Tiny\Html\div;
use function Tiny\Html\h1;
use function Tiny\Html\span;

$view = (
    div(
        h1('JavaScript Counter App'),
        div(
            button('+')->id('add')->onclick('handleAdd()'),
            span(0)->id('output'),
            button('-')->id('subtract'),
        )->class('counter-container')
    )
);

echo $view->TDom();
