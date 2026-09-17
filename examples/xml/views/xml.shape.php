<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;
use Pure\Core\XML;

/**
 * Address shape: fields follow the data key order; `city` is optional and
 * rendered only when the record provides it.
 */
function AddressShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        XML::address(
            XML::street(Slot::text('street')),
            Slot::if('city', Compile::shape(XML::city(Slot::text('city')))),
            XML::state(Slot::text('state')),
            XML::zip(Slot::text('zip'))
        )
    );
}

/**
 * The customer list as a shape file: `pure compile` precompiles it into
 * views/xml.pure.php, and app/controllers render the same shape without
 * precompiling.
 */
function XmlPageShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        XML::customers(
            XML::customer(
                XML::name('Charter Group'),
                Slot::each('addresses', AddressShape())
            )->id('55000')
        )
    );
}

return XmlPageShape();
