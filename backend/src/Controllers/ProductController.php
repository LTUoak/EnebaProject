<?php
declare(strict_types=1);

namespace App\Controllers;

use App\ApiError;
use App\Catalog;
use App\Database;
use App\Http;

/** The store page (list, filters, search) and the product page. */
final class ProductController
{
    private const DEFAULT_PER_PAGE = 12;
    private const MAX_PER_PAGE     = 48;

    /** How similar a title must be to the search text (0 = nothing in common, 1 = identical). */
    private const SEARCH_THRESHOLD = 0.35;

    /** Allowed values of ?sort= and the ORDER BY each one means. Anything else is ignored. */
    private const SORTS = [
        'relevance'  => 'relevance DESC, c.title ASC',
        'newest'     => 'c.release_date DESC NULLS LAST, c.title ASC',
        'price_asc'  => 'c.price_cents ASC, c.title ASC',
        'price_desc' => 'c.price_cents DESC, c.title ASC',
        'discount'   => 'c.discount_percent DESC NULLS LAST, c.title ASC',
        'title'      => 'c.title ASC',
    ];

    /**
     * GET /api/products — the store list.
     *
     * Query string (all optional):
     *   search      text to look for in titles; tolerates typos
     *   platform    one or more, comma-separated: Steam,GOG
     *   region      one or more, comma-separated: GLOBAL,EUROPE
     *   min_price   in cents
     *   max_price   in cents
     *   on_sale     1 = only discounted products
     *   sort        relevance | newest | price_asc | price_desc | discount | title
     *   page        1, 2, 3 ...
     *   per_page    default 12, at most 48
     */
    public static function index(): array
    {
        $where  = [];
        $params = [];

        // --- search --------------------------------------------------------
        $search    = mb_substr(Http::query('search'), 0, 100);
        $relevance = '0';
        if ($search !== '') {
            // ILIKE finds exact fragments ("witch" in "The Witcher 3").
            // word_similarity() from pg_trgm finds near misses ("cyberpnuk").
            $params[':like'] = '%' . addcslashes($search, '%_\\') . '%';
            if (mb_strlen($search) >= 3) {
                $params[':search'] = $search;
                $where[]   = '(c.title ILIKE :like OR word_similarity(:search, c.title) >= ' . self::SEARCH_THRESHOLD . ')';
                // Similarity decides the order ("witch" ranks "The Witcher 3" above
                // "Nintendo Switch"); containing the exact text adds a bonus.
                $relevance = '(word_similarity(:search, c.title) + CASE WHEN c.title ILIKE :like THEN 0.5 ELSE 0 END)';
            } else {
                $where[]   = 'c.title ILIKE :like';
                $relevance = '1';
            }
        }

        // --- filters -------------------------------------------------------
        foreach (['platform', 'region'] as $column) {
            $values = Http::queryList($column);
            if ($values === []) {
                continue;
            }
            $names = [];
            foreach ($values as $i => $value) {
                $names[] = ":{$column}{$i}";
                $params[":{$column}{$i}"] = $value;
            }
            $where[] = "c.$column IN (" . implode(', ', $names) . ')';
        }

        $minPrice = Http::queryInt('min_price', 0, 10_000_000);
        if ($minPrice !== null) {
            $where[] = 'c.price_cents >= ' . $minPrice;
        }
        $maxPrice = Http::queryInt('max_price', 0, 10_000_000);
        if ($maxPrice !== null) {
            $where[] = 'c.price_cents <= ' . $maxPrice;
        }
        if (Http::query('on_sale') === '1') {
            $where[] = 'c.discount_percent IS NOT NULL';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        // --- sorting and paging --------------------------------------------
        $sort = Http::query('sort');
        if (!isset(self::SORTS[$sort]) || ($sort === 'relevance' && $search === '')) {
            $sort = $search !== '' ? 'relevance' : 'newest';
        }
        $perPage = Http::queryInt('per_page', 1, self::MAX_PER_PAGE) ?? self::DEFAULT_PER_PAGE;
        $page    = Http::queryInt('page', 1, 100_000) ?? 1;

        $total = (int) Database::one(
            Catalog::CARDS . " SELECT count(*) AS total FROM cards c $whereSql",
            $params
        )['total'];

        $rows = Database::all(
            Catalog::CARDS . "
            SELECT c.*, $relevance AS relevance
            FROM cards c
            $whereSql
            ORDER BY " . self::SORTS[$sort] . '
            LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );

        return [
            'items'       => array_map(Catalog::card(...), $rows),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'sort'        => $sort,
        ];
    }

    /** GET /api/products/filters — the values the store's filter panel can offer. */
    public static function filters(): array
    {
        $count = fn (string $column) => array_map(
            fn ($row) => ['value' => $row['value'], 'count' => (int) $row['count']],
            Database::all(
                Catalog::CARDS . " SELECT c.$column AS value, count(*) AS count
                                   FROM cards c GROUP BY c.$column ORDER BY count(*) DESC, c.$column"
            )
        );
        $price = Database::one(
            Catalog::CARDS . ' SELECT min(c.price_cents) AS min, max(c.price_cents) AS max FROM cards c'
        );

        return [
            'platforms' => $count('platform'),
            'regions'   => $count('region'),
            'price'     => ['min_cents' => (int) $price['min'], 'max_cents' => (int) $price['max']],
            'sorts'     => array_keys(self::SORTS),
        ];
    }

    /** GET /api/products/{slug} — one product with every seller's offer, cheapest in-stock first. */
    public static function show(array $params): array
    {
        $product = Database::one(
            'SELECT id, slug, title, description, cover_image_url, platform, region, release_date
             FROM products WHERE slug = :slug',
            [':slug' => $params['slug']]
        );
        if ($product === null) {
            throw new ApiError(404, 'Product not found.');
        }

        $offers = Database::all(
            'SELECT * FROM (
                 SELECT f.id, u.display_name AS seller_name, f.price_cents, f.old_price_cents,
                        (SELECT count(*) FROM game_keys k
                          WHERE k.offer_id = f.id AND k.order_item_id IS NULL) AS stock
                 FROM offers f
                 JOIN users u ON u.id = f.seller_id
                 WHERE f.product_id = :id AND f.is_active
             ) o
             ORDER BY (o.stock > 0) DESC, o.price_cents ASC, o.id ASC',
            [':id' => $product['id']]
        );

        $product['id']     = (int) $product['id'];
        $product['offers'] = array_map(function (array $offer): array {
            $price    = (int) $offer['price_cents'];
            $oldPrice = Catalog::intOrNull($offer['old_price_cents']);
            return [
                'id'               => (int) $offer['id'],
                'seller_name'      => $offer['seller_name'],
                'price_cents'      => $price,
                'old_price_cents'  => $oldPrice,
                'discount_percent' => Catalog::discountPercent($price, $oldPrice),
                'stock'            => (int) $offer['stock'],
                'in_stock'         => (int) $offer['stock'] > 0,
            ];
        }, $offers);

        return ['product' => $product];
    }
}
