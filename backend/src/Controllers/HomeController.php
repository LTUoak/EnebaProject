<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Catalog;
use App\Database;

/**
 * The homepage blocks. Each block has its own endpoint and its own query,
 * so the frontend loads them separately and one slow or failing block does
 * not affect the others.
 */
final class HomeController
{
    private const BLOCK_SIZE = 8;

    /** GET /api/home/banners — the promo banners, from the banners table. */
    public static function banners(): array
    {
        $rows = Database::all(
            'SELECT id, title, subtitle, image_url, link_url
             FROM banners
             WHERE is_active
             ORDER BY sort_order, id'
        );
        return ['items' => array_map(fn ($row) => ['id' => (int) $row['id']] + $row, $rows)];
    }

    /** GET /api/home/best-sellers — products with the most copies sold in paid orders. */
    public static function bestSellers(): array
    {
        $rows = Database::all(
            Catalog::CARDS . ',
            sales AS (
                SELECT f.product_id, sum(oi.quantity) AS sold
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id AND o.status = \'paid\'
                JOIN offers f ON f.id = oi.offer_id
                GROUP BY f.product_id
            )
            SELECT c.*
            FROM cards c
            JOIN sales s ON s.product_id = c.id
            ORDER BY s.sold DESC, c.title ASC
            LIMIT ' . self::BLOCK_SIZE
        );
        return ['items' => array_map(Catalog::card(...), $rows)];
    }

    /** GET /api/home/deals — products in stock with the biggest discount. */
    public static function deals(): array
    {
        $rows = Database::all(
            Catalog::CARDS . '
            SELECT c.*
            FROM cards c
            WHERE c.discount_percent IS NOT NULL AND c.in_stock
            ORDER BY c.discount_percent DESC, c.title ASC
            LIMIT ' . self::BLOCK_SIZE
        );
        return ['items' => array_map(Catalog::card(...), $rows)];
    }
}
