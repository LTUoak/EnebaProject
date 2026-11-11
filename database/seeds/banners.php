<?php
/**
 * Seed data: homepage banners.
 *
 * 'link' is either ['product' => [<game>, <device>, <platform>, <region>]]
 * (resolved to that product's page by the seeder) or a plain URL path.
 * 'hero' names a game from images.php whose wide artwork is the background.
 */

return [
    [
        'title'    => 'Split Fiction',
        'subtitle' => 'Two worlds, one co-op adventure. Now 34% off.',
        'hero'     => 'Split Fiction',
        'link'     => ['product' => ['Split Fiction', 'PC', 'Steam', 'GLOBAL']],
    ],
    [
        'title'    => 'Red Dead Redemption 2',
        'subtitle' => 'The western epic at its lowest price: 75% off.',
        'hero'     => 'Red Dead Redemption 2',
        'link'     => ['product' => ['Red Dead Redemption 2', 'PC', 'Rockstar', 'GLOBAL']],
    ],
    [
        'title'    => 'Steam deals',
        'subtitle' => 'Top PC games at lower prices from several sellers.',
        'hero'     => 'Elden Ring',
        'link'     => '/store?platform=Steam',
    ],
];
