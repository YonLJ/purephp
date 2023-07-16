<?php
require '../../vendor/autoload.php';

use function Tiny\button;
use function Tiny\div;
use function Tiny\h1;
use function Tiny\span;

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
