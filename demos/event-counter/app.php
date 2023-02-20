<?php
require '../../vendor/autoload.php';

use Tiny\VDom;
use function Tiny\button;
use function Tiny\div;
use function Tiny\h1;
use function Tiny\span;

$view = (
    div(
        h1('JavaScript Counter App'),
        div(
            button('-')->id('subtract'),
            span(0)->id('output'),
            button('+')->id('add')->onclick('handleAdd()')
        )->class('counter-container')
    )
);

$vDom = new VDom();
$vDom->outputHTML($view);
