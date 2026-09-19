<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;
use Pure\Core\XML;

use function Pure\Component\{register, render};

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
            Slot::if('city', XML::city(Slot::text('city'))),
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

register('Xml', __FILE__, static fn (): Shape => XmlPageShape());

/**
 * The xml page: the document comes from views/xml.shape.php (precompiled with
 * `pure compile`). The XML declaration is not part of the tree, so it is
 * prepended manually.
 *
 * @param array<string, mixed> $data The page data from the controller.
 */
function xmlPage(array $data): Raw
{
    return Raw::of('<?xml version="1.0"?>' . (string)render('Xml', addresses: $data['addresses']));
}
