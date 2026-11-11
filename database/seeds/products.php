<?php
/**
 * Seed data: products with their offers.
 *
 * Each product:
 *   game, device, platform, region  -> combined into the title, e.g.
 *                                      "Elden Ring (PC) Steam Key GLOBAL"
 *   released                        -> release date (YYYY-MM-DD)
 *   about                           -> one-line description
 *   sold                            -> copies sold in the past; the seeder turns
 *                                      these into paid orders so the homepage
 *                                      "best sellers" block has data to rank
 *   offers                          -> [seller, price in cents, old price in cents or null, keys in stock]
 *                                      The first offer is the cheapest one.
 *
 * Prices are invented for the demo. Sellers are the display names from users.php.
 */

return [
    [
        'game' => 'Elden Ring', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2022-02-25', 'sold' => 14,
        'about' => 'Open-world action RPG set in the Lands Between.',
        'offers' => [['KeyHarbor', 3499, 5999, 12], ['PixelVault', 3649, null, 8], ['NordicKeys', 3799, null, 10]],
    ],
    [
        'game' => 'Elden Ring', 'device' => 'Xbox Series X|S', 'platform' => 'Xbox Live', 'region' => 'EUROPE',
        'released' => '2022-02-25', 'sold' => 4,
        'about' => 'Open-world action RPG set in the Lands Between.',
        'offers' => [['GameLantern', 4199, null, 6], ['TurboCodes', 4349, null, 5]],
    ],
    [
        'game' => 'Cyberpunk 2077', 'device' => 'PC', 'platform' => 'GOG', 'region' => 'GLOBAL',
        'released' => '2020-12-10', 'sold' => 11,
        'about' => 'Open-world RPG set in the neon metropolis of Night City.',
        'offers' => [['PixelVault', 2399, 5999, 15], ['KeyHarbor', 2549, null, 9], ['TurboCodes', 2699, null, 7]],
    ],
    [
        'game' => 'Red Dead Redemption 2', 'device' => 'PC', 'platform' => 'Rockstar', 'region' => 'GLOBAL',
        'released' => '2019-11-05', 'sold' => 18,
        'about' => 'Western epic following outlaw Arthur Morgan across America.',
        'offers' => [['NordicKeys', 1499, 5999, 14], ['KeyHarbor', 1599, null, 10], ['GameLantern', 1649, null, 8], ['PixelVault', 1799, null, 6]],
    ],
    [
        'game' => 'Red Dead Redemption 2', 'device' => 'Xbox One', 'platform' => 'Xbox Live', 'region' => 'EUROPE',
        'released' => '2018-10-26', 'sold' => 5,
        'about' => 'Western epic following outlaw Arthur Morgan across America.',
        'offers' => [['TurboCodes', 1899, null, 7], ['GameLantern', 1999, null, 5]],
    ],
    [
        'game' => 'Grand Theft Auto V', 'device' => 'PC', 'platform' => 'Rockstar', 'region' => 'GLOBAL',
        'released' => '2015-04-14', 'sold' => 16,
        'about' => 'Open-world crime story across the city of Los Santos.',
        'offers' => [['KeyHarbor', 1299, 2999, 15], ['TurboCodes', 1349, null, 12], ['NordicKeys', 1449, null, 9]],
    ],
    [
        'game' => 'The Witcher 3: Wild Hunt', 'device' => 'PC', 'platform' => 'GOG', 'region' => 'GLOBAL',
        'released' => '2015-05-19', 'sold' => 7,
        'about' => 'Story-driven RPG about monster hunter Geralt of Rivia.',
        'offers' => [['PixelVault', 899, 2999, 10], ['NordicKeys', 999, null, 8]],
    ],
    [
        'game' => "Baldur's Gate 3", 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2023-08-03', 'sold' => 12,
        'about' => 'Party-based RPG set in the world of Dungeons & Dragons.',
        'offers' => [['GameLantern', 4799, 5999, 9], ['KeyHarbor', 4899, null, 11], ['PixelVault', 5099, null, 6]],
    ],
    [
        'game' => 'Hogwarts Legacy', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2023-02-10', 'sold' => 9,
        'about' => 'Open-world action RPG set in the wizarding world of the 1800s.',
        'offers' => [['NordicKeys', 2199, 5999, 10], ['TurboCodes', 2299, null, 8], ['GameLantern', 2449, null, 7]],
    ],
    [
        'game' => 'Split Fiction', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2025-03-06', 'sold' => 13,
        'about' => 'Co-op adventure that jumps between sci-fi and fantasy worlds.',
        'offers' => [['KeyHarbor', 3299, 4999, 10], ['PixelVault', 3449, null, 9], ['TurboCodes', 3599, null, 6]],
    ],
    [
        'game' => 'Split Fiction', 'device' => 'Xbox Series X|S', 'platform' => 'Xbox Live', 'region' => 'EUROPE',
        'released' => '2025-03-06', 'sold' => 3,
        'about' => 'Co-op adventure that jumps between sci-fi and fantasy worlds.',
        'offers' => [['GameLantern', 3899, null, 5], ['NordicKeys', 3999, null, 5]],
    ],
    [
        'game' => 'EA SPORTS FC 25', 'device' => 'PC', 'platform' => 'EA App', 'region' => 'GLOBAL',
        'released' => '2024-09-27', 'sold' => 10,
        'about' => 'Football simulation with licensed clubs, leagues and players.',
        'offers' => [['TurboCodes', 1999, 6999, 12], ['KeyHarbor', 2149, null, 10], ['NordicKeys', 2299, null, 8]],
    ],
    [
        'game' => 'EA SPORTS FC 25', 'device' => 'Xbox One/Series X|S', 'platform' => 'Xbox Live', 'region' => 'UNITED STATES',
        'released' => '2024-09-27', 'sold' => 2,
        'about' => 'Football simulation with licensed clubs, leagues and players.',
        'offers' => [['KeyHarbor', 2399, null, 5], ['GameLantern', 2499, null, 5]],
    ],
    [
        'game' => 'It Takes Two', 'device' => 'PC', 'platform' => 'EA App', 'region' => 'GLOBAL',
        'released' => '2021-03-26', 'sold' => 6,
        'about' => 'Co-op platform adventure made for two players.',
        'offers' => [['PixelVault', 1199, 3999, 9], ['GameLantern', 1299, null, 7]],
    ],
    [
        'game' => 'Forza Horizon 5', 'device' => 'PC/Xbox', 'platform' => 'Xbox Live', 'region' => 'GLOBAL',
        'released' => '2021-11-09', 'sold' => 8,
        'about' => 'Open-world racing across the landscapes of Mexico.',
        'offers' => [['NordicKeys', 2499, 5999, 10], ['TurboCodes', 2649, null, 8]],
    ],
    [
        'game' => 'Sea of Thieves', 'device' => 'PC/Xbox', 'platform' => 'Xbox Live', 'region' => 'EUROPE',
        'released' => '2018-03-20', 'sold' => 2,
        'about' => 'Shared-world pirate adventure for you and your crew.',
        'offers' => [['GameLantern', 1799, null, 6], ['KeyHarbor', 1899, null, 5]],
    ],
    [
        'game' => 'God of War', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2022-01-14', 'sold' => 7,
        'about' => 'Kratos and his son Atreus journey through the Norse realms.',
        'offers' => [['PixelVault', 1899, 4999, 9], ['NordicKeys', 1999, null, 7], ['TurboCodes', 2099, null, 6]],
    ],
    [
        'game' => 'God of War Ragnarök', 'slug_name' => 'God of War Ragnarok',
        'device' => 'PS5', 'platform' => 'PSN', 'region' => 'EUROPE',
        'released' => '2022-11-09', 'sold' => 4,
        'about' => 'Kratos and Atreus face the coming of Ragnarök.',
        'offers' => [['KeyHarbor', 4499, null, 6], ['GameLantern', 4699, null, 4]],
    ],
    [
        'game' => "Marvel's Spider-Man Remastered", 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2022-08-12', 'sold' => 5,
        'about' => 'Swing through New York as an experienced Peter Parker.',
        'offers' => [['TurboCodes', 2799, 5999, 8], ['PixelVault', 2949, null, 6]],
    ],
    [
        'game' => 'Helldivers 2', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2024-02-08', 'sold' => 11,
        'about' => 'Co-op squad shooter fighting for Super Earth.',
        'offers' => [['NordicKeys', 2899, 3999, 12], ['KeyHarbor', 2999, null, 9], ['GameLantern', 3149, null, 7]],
    ],
    [
        'game' => 'Stardew Valley', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2016-02-26', 'sold' => 9,
        'about' => 'Farming and life simulation in a small countryside town.',
        'offers' => [['PixelVault', 899, 1399, 12], ['TurboCodes', 949, null, 10]],
    ],
    [
        'game' => 'Hades', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2020-09-17', 'sold' => 6,
        'about' => 'Roguelike dungeon crawler about escaping the Greek underworld.',
        'offers' => [['GameLantern', 999, 2499, 9], ['NordicKeys', 1099, null, 8]],
    ],
    [
        'game' => 'Terraria', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2011-05-16', 'sold' => 5,
        'about' => '2D sandbox adventure of digging, building and fighting.',
        'offers' => [['KeyHarbor', 499, 999, 14], ['PixelVault', 549, null, 10]],
    ],
    [
        'game' => 'Hollow Knight', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2017-02-24', 'sold' => 7,
        'about' => 'Hand-drawn action adventure through a ruined insect kingdom.',
        'offers' => [['TurboCodes', 799, 1499, 10], ['GameLantern', 849, null, 8]],
    ],
    [
        'game' => 'The Legend of Zelda: Tears of the Kingdom', 'device' => 'Nintendo Switch', 'platform' => 'Nintendo eShop', 'region' => 'EUROPE',
        'released' => '2023-05-12', 'sold' => 6,
        'about' => 'Explore the land and skies of Hyrule in an open-world adventure.',
        'offers' => [['NordicKeys', 5299, 6999, 7], ['KeyHarbor', 5499, null, 5]],
    ],
    [
        'game' => 'Mario Kart 8 Deluxe', 'device' => 'Nintendo Switch', 'platform' => 'Nintendo eShop', 'region' => 'EUROPE',
        'released' => '2017-04-28', 'sold' => 8,
        'about' => 'Kart racing for up to four players on one console.',
        'offers' => [['PixelVault', 4499, 5999, 8], ['TurboCodes', 4649, null, 6]],
    ],
    [
        // The second offer has no keys, to show a sold-out offer on the product page.
        'game' => 'Palworld', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2024-01-19', 'sold' => 4,
        'about' => 'Open-world survival and crafting game with collectible creatures.',
        'offers' => [['GameLantern', 2199, 2899, 8], ['KeyHarbor', 2299, null, 0]],
    ],
    [
        'game' => 'Black Myth: Wukong', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2024-08-20', 'sold' => 5,
        'about' => 'Action RPG inspired by the Chinese novel Journey to the West.',
        'offers' => [['NordicKeys', 4599, 5999, 7], ['PixelVault', 4749, null, 6]],
    ],
    [
        'game' => 'Resident Evil 4', 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2023-03-24', 'sold' => 3,
        'about' => 'Remake of the survival horror classic starring Leon S. Kennedy.',
        'offers' => [['TurboCodes', 1799, 3999, 8], ['KeyHarbor', 1899, null, 7]],
    ],
    [
        'game' => "Ghost of Tsushima Director's Cut", 'device' => 'PC', 'platform' => 'Steam', 'region' => 'GLOBAL',
        'released' => '2024-05-16', 'sold' => 4,
        'about' => 'Samurai open-world adventure set during the Mongol invasion of Japan.',
        'offers' => [['GameLantern', 3799, 5999, 7], ['NordicKeys', 3949, null, 5]],
    ],
];
