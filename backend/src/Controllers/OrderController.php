<?php
declare(strict_types=1);

namespace App\Controllers;

use App\ApiError;
use App\Auth;
use App\Catalog;
use App\Database;
use App\Http;

/** Checkout and order history. Every endpoint here requires sign-in. */
final class OrderController
{
    /**
     * POST /api/orders — checkout.
     * Body: {items: [{offer_id, quantity}], expected_total_cents?: number}
     *
     * Payment is simulated and always succeeds. Everything happens in one
     * database transaction: the order, its items and the keys handed to the
     * buyer are all saved, or (if anything goes wrong) none of them are.
     */
    public static function create(): array
    {
        $user       = Auth::requireUser();
        $body       = Http::body();
        $quantities = Catalog::parseItems($body);
        $pdo        = Database::pdo();

        $pdo->beginTransaction();
        try {
            $lines = Catalog::cartLines($quantities);
            foreach ($lines as $line) {
                if ($line['status'] === 'unavailable') {
                    throw new ApiError(409, 'An offer in your cart is no longer available. Please review your cart.');
                }
            }
            $total = array_sum(array_column($lines, 'line_total_cents'));

            // If the cart page showed a different total, a price changed in between:
            // stop instead of charging an amount the buyer did not see.
            $expected = $body['expected_total_cents'] ?? null;
            if (is_int($expected) && $expected !== $total) {
                throw new ApiError(409, 'Prices have changed since you opened your cart. Please review your cart.');
            }

            $orderId = (int) Database::one(
                "INSERT INTO orders (user_id, status, total_cents) VALUES (:user, 'pending', :total) RETURNING id",
                [':user' => $user['id'], ':total' => $total]
            )['id'];

            foreach ($lines as $line) {
                $itemId = (int) Database::one(
                    'INSERT INTO order_items (order_id, offer_id, quantity, unit_price_cents)
                     VALUES (:order, :offer, :quantity, :price) RETURNING id',
                    [
                        ':order'    => $orderId,
                        ':offer'    => $line['offer_id'],
                        ':quantity' => $line['quantity'],
                        ':price'    => $line['unit_price_cents'],
                    ]
                )['id'];

                // Hand over the keys: pick unsold keys of this offer and stamp them
                // with the order item. "FOR UPDATE SKIP LOCKED" means two buyers
                // checking out at the same moment can never receive the same key.
                $claimed = Database::all(
                    'UPDATE game_keys
                     SET order_item_id = :item, sold_at = now()
                     WHERE id IN (
                         SELECT id FROM game_keys
                         WHERE offer_id = :offer AND order_item_id IS NULL
                         ORDER BY id
                         LIMIT ' . (int) $line['quantity'] . '
                         FOR UPDATE SKIP LOCKED
                     )
                     RETURNING id',
                    [':item' => $itemId, ':offer' => $line['offer_id']]
                );
                if (count($claimed) < $line['quantity']) {
                    throw new ApiError(409, sprintf(
                        'Not enough keys in stock for "%s" from %s. Please review your cart.',
                        $line['product']['title'],
                        $line['seller_name']
                    ));
                }
            }

            // Simulated payment succeeded: mark the order as paid.
            Database::all("UPDATE orders SET status = 'paid', paid_at = now() WHERE id = :id", [':id' => $orderId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Http::send(['order' => self::load($user['id'], $orderId)[0]], 201);
    }

    /** GET /api/orders — the signed-in user's orders, newest first, with their keys. */
    public static function index(): array
    {
        return ['items' => self::load(Auth::requireUser()['id'])];
    }

    /** GET /api/orders/{id} — one order. Someone else's order answers "not found". */
    public static function show(array $params): array
    {
        $user = Auth::requireUser();
        if (!preg_match('/^\d{1,18}$/', $params['id'])) {
            throw new ApiError(404, 'Order not found.');
        }
        $orders = self::load($user['id'], (int) $params['id']);
        if ($orders === []) {
            throw new ApiError(404, 'Order not found.');
        }
        return ['order' => $orders[0]];
    }

    /**
     * Loads a user's orders (or one of them) with items and keys, using three
     * queries in total no matter how many orders there are.
     */
    private static function load(int $userId, ?int $orderId = null): array
    {
        $params = [':user' => $userId];
        $filter = '';
        if ($orderId !== null) {
            $filter = 'AND id = :id';
            $params[':id'] = $orderId;
        }

        $orders = Database::all(
            "SELECT id, status, total_cents,
                    to_char(created_at AT TIME ZONE 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') AS created_at,
                    to_char(paid_at    AT TIME ZONE 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') AS paid_at
             FROM orders
             WHERE user_id = :user $filter
             ORDER BY orders.created_at DESC, id DESC",
            $params
        );
        if ($orders === []) {
            return [];
        }

        $orderIds = implode(', ', array_map(fn ($order) => (int) $order['id'], $orders));

        $items = Database::all(
            "SELECT oi.id, oi.order_id, oi.offer_id, oi.quantity, oi.unit_price_cents,
                    u.display_name AS seller_name,
                    p.slug, p.title, p.cover_image_url, p.platform, p.region
             FROM order_items oi
             JOIN offers f   ON f.id = oi.offer_id
             JOIN users u    ON u.id = f.seller_id
             JOIN products p ON p.id = f.product_id
             WHERE oi.order_id IN ($orderIds)
             ORDER BY oi.id"
        );

        $keysByItem = [];
        if ($items !== []) {
            $itemIds = implode(', ', array_map(fn ($item) => (int) $item['id'], $items));
            foreach (Database::all(
                "SELECT order_item_id, key_code FROM game_keys WHERE order_item_id IN ($itemIds) ORDER BY id"
            ) as $key) {
                $keysByItem[$key['order_item_id']][] = $key['key_code'];
            }
        }

        $itemsByOrder = [];
        foreach ($items as $item) {
            $itemsByOrder[$item['order_id']][] = [
                'offer_id'         => (int) $item['offer_id'],
                'quantity'         => (int) $item['quantity'],
                'unit_price_cents' => (int) $item['unit_price_cents'],
                'line_total_cents' => (int) $item['quantity'] * (int) $item['unit_price_cents'],
                'seller_name'      => $item['seller_name'],
                'product'          => [
                    'slug'            => $item['slug'],
                    'title'           => $item['title'],
                    'cover_image_url' => $item['cover_image_url'],
                    'platform'        => $item['platform'],
                    'region'          => $item['region'],
                ],
                'keys'             => $keysByItem[$item['id']] ?? [],
            ];
        }

        return array_map(fn (array $order) => [
            'id'          => (int) $order['id'],
            'status'      => $order['status'],
            'total_cents' => (int) $order['total_cents'],
            'created_at'  => $order['created_at'],
            'paid_at'     => $order['paid_at'],
            'items'       => $itemsByOrder[$order['id']] ?? [],
        ], $orders);
    }
}
