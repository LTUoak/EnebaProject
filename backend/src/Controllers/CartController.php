<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Catalog;
use App\Http;

/**
 * The cart itself lives in the browser. This endpoint lets the cart page ask
 * "what do these items cost right now, and are they still in stock?" so it
 * never shows prices the browser remembered from earlier.
 */
final class CartController
{
    /** POST /api/cart/quote — body: {items: [{offer_id, quantity}]}. No sign-in needed. */
    public static function quote(): array
    {
        $lines = Catalog::cartLines(Catalog::parseItems(Http::body()));

        return [
            'items'        => $lines,
            'total_cents'  => array_sum(array_column($lines, 'line_total_cents')),
            'can_checkout' => array_all($lines, fn ($line) => $line['status'] === 'ok'),
        ];
    }
}
