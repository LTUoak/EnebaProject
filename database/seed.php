<?php
/**
 * Seed runner: fills the database with sample data from database/seeds.
 *
 * Usage (from the repo root):
 *   php database/seed.php           Seeds an empty database. Does nothing if data already exists.
 *   php database/seed.php --reset   DELETES all users, products, offers, keys, orders and banners,
 *                                   then seeds again from scratch.
 *
 * Everything happens in one transaction: either all sample data is saved or none.
 * The result is the same on every run (fixed random seed), apart from order dates,
 * which are counted back from today.
 */

declare(strict_types=1);

require __DIR__ . '/connection.php';

const DEMO_PASSWORD_VARIABLE = 'SEED_DEMO_PASSWORD';
const ORDER_HISTORY_DAYS     = 60;

$users    = require __DIR__ . '/seeds/users.php';
$products = require __DIR__ . '/seeds/products.php';
$banners  = require __DIR__ . '/seeds/banners.php';

$reset = in_array('--reset', $argv, true);
$db    = connect();

// --- safety check -------------------------------------------------------

$existing = (int) $db->query('SELECT (SELECT count(*) FROM users) + (SELECT count(*) FROM products)')->fetchColumn();
if ($existing > 0 && !$reset) {
    echo "The database already contains data, so nothing was changed.\n";
    echo "To delete everything and seed again: php database/seed.php --reset\n";
    exit(0);
}

mt_srand(2026); // fixed seed: the same keys and orders are generated on every run

$db->beginTransaction();
try {
    if ($reset) {
        $db->exec('TRUNCATE game_keys, order_items, orders, offers, products, banners, users RESTART IDENTITY');
    }

    // --- users ------------------------------------------------------------

    $insertUser = $db->prepare(
        'INSERT INTO users (email, password_hash, display_name, is_seller) VALUES (?, ?, ?, ?) RETURNING id'
    );
    $addUser = function (array $user, bool $isSeller, string $password) use ($insertUser): int {
        $insertUser->bindValue(1, $user['email']);
        $insertUser->bindValue(2, password_hash($password, PASSWORD_DEFAULT));
        $insertUser->bindValue(3, $user['display_name']);
        $insertUser->bindValue(4, $isSeller, PDO::PARAM_BOOL);
        $insertUser->execute();
        return (int) $insertUser->fetchColumn();
    };

    $sellerIds = []; // display name => user id
    foreach ($users['sellers'] as $seller) {
        $sellerIds[$seller['display_name']] = $addUser($seller, true, bin2hex(random_bytes(16)));
    }

    $buyerIds = [];
    foreach ($users['buyers'] as $buyer) {
        $password   = !empty($buyer['can_sign_in']) ? demoPassword() : bin2hex(random_bytes(16));
        $buyerIds[] = $addUser($buyer, false, $password);
    }

    // --- products, offers and keys in stock --------------------------------

    $insertProduct = $db->prepare(
        'INSERT INTO products (slug, title, description, cover_image_url, platform, region, release_date)
         VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $insertOffer = $db->prepare(
        'INSERT INTO offers (product_id, seller_id, price_cents, old_price_cents) VALUES (?, ?, ?, ?) RETURNING id'
    );
    $insertKey = $db->prepare(
        'INSERT INTO game_keys (offer_id, key_code, order_item_id, sold_at) VALUES (?, ?, ?, ?)'
    );

    $slugs       = []; // "game|device|platform|region" => slug, used to resolve banner links
    $soldUnits   = []; // one entry per copy sold in the past: ['offer_id' => ..., 'price' => ...]
    $offerCount  = 0;
    $stockCount  = 0;

    foreach ($products as $product) {
        $title = sprintf('%s (%s) %s Key %s', $product['game'], $product['device'], $product['platform'], $product['region']);
        $slug  = slugify(sprintf(
            '%s %s %s Key %s',
            $product['slug_name'] ?? $product['game'], $product['device'], $product['platform'], $product['region']
        ));
        $slugs[implode('|', [$product['game'], $product['device'], $product['platform'], $product['region']])] = $slug;

        $insertProduct->execute([
            $slug,
            $title,
            $product['about'],
            'https://placehold.co/600x800/1f0a4d/ffffff?text=' . rawurlencode($product['game']),
            $product['platform'],
            $product['region'],
            $product['released'],
        ]);
        $productId = (int) $insertProduct->fetchColumn();

        foreach ($product['offers'] as $index => [$sellerName, $price, $oldPrice, $stock]) {
            if (!isset($sellerIds[$sellerName])) {
                throw new RuntimeException("Unknown seller '$sellerName' in products.php ($title)");
            }
            $insertOffer->execute([$productId, $sellerIds[$sellerName], $price, $oldPrice]);
            $offerId = (int) $insertOffer->fetchColumn();
            $offerCount++;

            for ($i = 0; $i < $stock; $i++) {
                $insertKey->execute([$offerId, fakeKey(), null, null]);
                $stockCount++;
            }

            // Past sales all go to the first (cheapest) offer, as real buyers would choose.
            if ($index === 0) {
                for ($i = 0; $i < $product['sold']; $i++) {
                    $soldUnits[] = ['offer_id' => $offerId, 'price' => $price];
                }
            }
        }
    }

    // --- past orders (so "best sellers" and order history have data) -------

    $insertOrder = $db->prepare(
        "INSERT INTO orders (user_id, status, total_cents, created_at, paid_at)
         VALUES (?, 'paid', ?, ?, ?) RETURNING id"
    );
    $insertItem = $db->prepare(
        'INSERT INTO order_items (order_id, offer_id, quantity, unit_price_cents) VALUES (?, ?, ?, ?) RETURNING id'
    );

    shuffle($soldUnits); // uses the fixed random seed
    $orderCount = 0;
    $soldCount  = 0;
    $now        = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    while ($soldUnits !== []) {
        // Each order buys 1 to 3 copies; copies of the same offer become quantity 2 or 3.
        $units = array_splice($soldUnits, 0, mt_rand(1, 3));
        $lines = []; // offer id => ['quantity' => ..., 'price' => ...]
        foreach ($units as $unit) {
            $lines[$unit['offer_id']] ??= ['quantity' => 0, 'price' => $unit['price']];
            $lines[$unit['offer_id']]['quantity']++;
        }
        $total = array_sum(array_map(fn ($line) => $line['quantity'] * $line['price'], $lines));

        $createdAt = $now->modify(sprintf('-%d days -%d minutes', mt_rand(1, ORDER_HISTORY_DAYS), mt_rand(0, 1439)));
        $paidAt    = $createdAt->modify('+1 minute');

        $insertOrder->execute([
            $buyerIds[$orderCount % count($buyerIds)],
            $total,
            $createdAt->format('Y-m-d H:i:sP'),
            $paidAt->format('Y-m-d H:i:sP'),
        ]);
        $orderId = (int) $insertOrder->fetchColumn();
        $orderCount++;

        foreach ($lines as $offerId => $line) {
            $insertItem->execute([$orderId, $offerId, $line['quantity'], $line['price']]);
            $itemId = (int) $insertItem->fetchColumn();

            // The keys this buyer received: already sold, so they do not count as stock.
            for ($i = 0; $i < $line['quantity']; $i++) {
                $insertKey->execute([$offerId, fakeKey(), $itemId, $paidAt->format('Y-m-d H:i:sP')]);
                $soldCount++;
            }
        }
    }

    // --- banners ----------------------------------------------------------

    $insertBanner = $db->prepare(
        'INSERT INTO banners (title, subtitle, image_url, link_url, sort_order) VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($banners as $position => $banner) {
        $link = $banner['link'];
        if (is_array($link)) {
            $key = implode('|', $link['product']);
            if (!isset($slugs[$key])) {
                throw new RuntimeException("Banner '{$banner['title']}' links to a product that is not in products.php");
            }
            $link = '/product/' . $slugs[$key];
        }
        $insertBanner->execute([$banner['title'], $banner['subtitle'], $banner['image'], $link, $position + 1]);
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fail('Seeding failed and nothing was saved: ' . $e->getMessage());
}

printf(
    "Seeded: %d sellers, %d buyers, %d products, %d offers, %d keys in stock, %d past orders (%d keys sold), %d banners.\n",
    count($sellerIds), count($buyerIds), count($products), $offerCount, $stockCount, $orderCount, $soldCount, count($banners)
);
echo "Demo sign-in: demo@example.com, password in backend/.env as " . DEMO_PASSWORD_VARIABLE . ".\n";

// --- helpers --------------------------------------------------------------

/** "Baldur's Gate 3 PC Steam Key GLOBAL" -> "baldurs-gate-3-pc-steam-key-global" */
function slugify(string $text): string
{
    $text = strtolower(str_replace("'", '', $text));
    return trim(preg_replace('/[^a-z0-9]+/', '-', $text), '-');
}

/** A clearly fake key such as "DEMO-7K2QX-M9RT4-PW3ZC". */
function fakeKey(): string
{
    static $used = [];
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $groups = [];
        for ($g = 0; $g < 3; $g++) {
            $group = '';
            for ($c = 0; $c < 5; $c++) {
                $group .= $alphabet[mt_rand(0, strlen($alphabet) - 1)];
            }
            $groups[] = $group;
        }
        $key = 'DEMO-' . implode('-', $groups);
    } while (isset($used[$key]));
    $used[$key] = true;
    return $key;
}

/**
 * Password for the demo buyer. Read from backend/.env; if it is not there yet,
 * a random one is generated and saved to backend/.env. It is never printed.
 */
function demoPassword(): string
{
    $env = readEnvFile();
    if (!empty($env[DEMO_PASSWORD_VARIABLE])) {
        return $env[DEMO_PASSWORD_VARIABLE];
    }
    $password = bin2hex(random_bytes(6));
    $current  = file_get_contents(ENV_FILE);
    $newline  = str_ends_with($current, "\n") ? '' : "\n";
    file_put_contents(ENV_FILE, $newline . DEMO_PASSWORD_VARIABLE . '=' . $password . "\n", FILE_APPEND);
    return $password;
}
