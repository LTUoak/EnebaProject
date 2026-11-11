<?php
declare(strict_types=1);

namespace App;

/**
 * Shared catalogue queries used by several controllers:
 *  - the "product card" (what a store tile shows), used by the store page and
 *    the homepage blocks, so a product looks the same everywhere;
 *  - reading and checking cart lines, used by the cart quote and by checkout.
 */
final class Catalog
{
    public const MAX_CART_LINES    = 20;
    public const MAX_QUANTITY      = 10;

    /**
     * SQL that builds a temporary result called "cards": one row per product
     * with the price a store tile should show. Queries append
     * "SELECT ... FROM cards c ..." to it.
     *
     *  offer_stock  every active offer with its number of unsold keys
     *  best_offer   per product, the offer to advertise: cheapest one that is
     *               in stock, or simply the cheapest if all are sold out
     *  cards        product fields + that offer's price, discount and stock flag
     */
    public const CARDS = <<<'SQL'
        WITH offer_stock AS (
            SELECT f.id, f.product_id, f.price_cents, f.old_price_cents,
                   (SELECT count(*) FROM game_keys k
                     WHERE k.offer_id = f.id AND k.order_item_id IS NULL) AS stock
            FROM offers f
            WHERE f.is_active
        ),
        best_offer AS (
            SELECT DISTINCT ON (product_id)
                   product_id, id AS offer_id, price_cents, old_price_cents, stock
            FROM offer_stock
            ORDER BY product_id, (stock > 0) DESC, price_cents ASC, id ASC
        ),
        cards AS (
            SELECT p.id, p.slug, p.title, p.cover_image_url, p.platform, p.region, p.release_date,
                   b.offer_id AS best_offer_id,
                   b.price_cents,
                   b.old_price_cents,
                   CASE WHEN b.old_price_cents IS NOT NULL
                        THEN round(100 - 100.0 * b.price_cents / b.old_price_cents)::int
                   END AS discount_percent,
                   (b.stock > 0) AS in_stock,
                   (SELECT count(*) FROM offer_stock o WHERE o.product_id = p.id) AS offer_count
            FROM products p
            JOIN best_offer b ON b.product_id = p.id
        )
        SQL;

    /** Turns a database row from "cards" into the JSON shape the frontend receives. */
    public static function card(array $row): array
    {
        return [
            'id'               => (int) $row['id'],
            'slug'             => $row['slug'],
            'title'            => $row['title'],
            'cover_image_url'  => $row['cover_image_url'],
            'platform'         => $row['platform'],
            'region'           => $row['region'],
            'release_date'     => $row['release_date'],
            'price_cents'      => (int) $row['price_cents'],
            'old_price_cents'  => self::intOrNull($row['old_price_cents']),
            'discount_percent' => self::intOrNull($row['discount_percent']),
            'in_stock'         => (bool) $row['in_stock'],
            'offer_count'      => (int) $row['offer_count'],
            'best_offer_id'    => (int) $row['best_offer_id'],
        ];
    }

    public static function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public static function discountPercent(int $price, ?int $oldPrice): ?int
    {
        return $oldPrice === null ? null : (int) round(100 - 100 * $price / $oldPrice);
    }

    /**
     * Reads {"items": [{"offer_id": 12, "quantity": 2}, ...]} from a request body.
     * Returns [offer id => quantity]. The browser only says WHAT it wants;
     * prices and stock always come from the database.
     */
    public static function parseItems(array $body): array
    {
        $items = $body['items'] ?? null;
        if (!is_array($items) || $items === [] || !array_is_list($items)) {
            throw new ApiError(422, 'The cart is empty.', ['items' => 'Add at least one item.']);
        }
        if (count($items) > self::MAX_CART_LINES) {
            throw new ApiError(422, 'The cart has too many different items.');
        }

        $quantities = [];
        foreach ($items as $item) {
            $offerId  = is_array($item) ? ($item['offer_id'] ?? null) : null;
            $quantity = is_array($item) ? ($item['quantity'] ?? null) : null;
            if (!is_int($offerId) || $offerId < 1 || !is_int($quantity) || $quantity < 1) {
                throw new ApiError(422, 'Each cart item needs a whole-number offer_id and quantity.');
            }
            $quantities[$offerId] = ($quantities[$offerId] ?? 0) + $quantity;
            if ($quantities[$offerId] > self::MAX_QUANTITY) {
                throw new ApiError(422, 'You can buy at most ' . self::MAX_QUANTITY . ' copies of one offer.');
            }
        }
        return $quantities;
    }

    /**
     * Looks up the current price, seller, product and stock for each cart line.
     * status: "ok", "insufficient_stock" (fewer keys than asked) or
     * "unavailable" (the offer no longer exists or was switched off).
     */
    public static function cartLines(array $quantities): array
    {
        $ids          = array_keys($quantities);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $rows = Database::all(
            "SELECT f.id AS offer_id, f.price_cents, f.old_price_cents, f.is_active,
                    u.display_name AS seller_name,
                    p.slug, p.title, p.cover_image_url, p.platform, p.region,
                    (SELECT count(*) FROM game_keys k
                      WHERE k.offer_id = f.id AND k.order_item_id IS NULL) AS stock
             FROM offers f
             JOIN users u    ON u.id = f.seller_id
             JOIN products p ON p.id = f.product_id
             WHERE f.id IN ($placeholders)",
            $ids
        );
        $byId = array_column($rows, null, 'offer_id');

        $lines = [];
        foreach ($quantities as $offerId => $quantity) {
            $row = $byId[$offerId] ?? null;
            if ($row === null || !$row['is_active']) {
                $lines[] = ['offer_id' => $offerId, 'quantity' => $quantity, 'status' => 'unavailable'];
                continue;
            }
            $price = (int) $row['price_cents'];
            $stock = (int) $row['stock'];
            $lines[] = [
                'offer_id'         => $offerId,
                'quantity'         => $quantity,
                'status'           => $stock >= $quantity ? 'ok' : 'insufficient_stock',
                'stock'            => $stock,
                'unit_price_cents' => $price,
                'old_price_cents'  => self::intOrNull($row['old_price_cents']),
                'line_total_cents' => $price * $quantity,
                'seller_name'      => $row['seller_name'],
                'product'          => [
                    'slug'            => $row['slug'],
                    'title'           => $row['title'],
                    'cover_image_url' => $row['cover_image_url'],
                    'platform'        => $row['platform'],
                    'region'          => $row['region'],
                ],
            ];
        }
        return $lines;
    }
}
