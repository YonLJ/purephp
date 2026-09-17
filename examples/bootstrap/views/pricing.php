<?php declare(strict_types=1);

require_once __DIR__ . '/../components/PageHeader.php';
require_once __DIR__ . '/../components/PricingHeader.php';
require_once __DIR__ . '/../components/CardDeck.php';
require_once __DIR__ . '/../components/PageFooter.php';

use Pure\Core\Raw;

use function Pure\Component\renderPage;

/**
 * The data of the pricing page: the four rendered blocks. The plain view
 * controller uses the same bindings, so both flavors render one page.
 *
 * @param array<string, mixed> $data The page data from the controller.
 * @return array{header: string, pricing: string, deck: string, footer: string}
 */
function pricingBindings(array $data): array
{
    return [
        'header' => (string)PageHeader('Company name', $data['header']['navs'], $data['header']['signUp']),
        'pricing' => (string)PricingHeader($data['pricing']['title'], $data['pricing']['desc']),
        'deck' => (string)CardDeck($data['deck']['cards']),
        'footer' => (string)PageFooter($data['footer']['logo'], $data['footer']['links']),
    ];
}

/**
 * The pricing page: the document skeleton comes from views/pricing.shape.php
 * (precompiled with `pure compile`), the four blocks are composed from the
 * component functions.
 *
 * @param array<string, mixed> $data The page data from the controller.
 */
function pricingPage(array $data): Raw
{
    return renderPage(__DIR__ . '/pricing.shape.php', pricingBindings($data));
}
