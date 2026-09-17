<?php declare(strict_types=1);

require_once '../../vendor/autoload.php';
require_once './components/PageHeader.php';
require_once './components/PricingHeader.php';
require_once './components/CardDeck.php';
require_once './components/PageFooter.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\body;
use function Pure\HTML\div;

/**
 * The compiled page: the shape is built once per process, every request only
 * binds data.
 */
function PricingPageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        body(
            Slot::child('header', PageHeaderShape('Company name')),
            Slot::child('pricing', PricingHeaderShape()),
            div(
                Slot::child('deck', CardDeckShape()),
                Slot::child('footer', PageFooterShape())
            )->class('container')
        )
    );
}

PricingPageShape()->print(require __DIR__ . '/bindings.php');
