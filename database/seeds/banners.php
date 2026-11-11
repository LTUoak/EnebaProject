<?php
/**
 * Seed data: homepage banners.
 *
 * 'link' is either ['product' => <game>, <device>, <platform>, <region>]
 * (resolved to that product's page by the seeder) or a plain URL path.
 * Images are generated placeholders until real artwork is chosen.
 */

return [
    [
        'title'    => 'Split Fiction',
        'subtitle' => 'Two worlds, one co-op adventure. Now 34% off.',
        'image'    => 'https://placehold.co/1600x500/4618ac/ffffff?text=Split+Fiction',
        'link'     => ['product' => ['Split Fiction', 'PC', 'Steam', 'GLOBAL']],
    ],
    [
        'title'    => 'Steam deals',
        'subtitle' => 'Top PC games at lower prices from several sellers.',
        'image'    => 'https://placehold.co/1600x500/1f0a4d/ffffff?text=Steam+Deals',
        'link'     => '/store?platform=Steam',
    ],
    [
        'title'    => 'Nintendo Switch hits',
        'subtitle' => 'eShop keys for Europe, delivered instantly.',
        'image'    => 'https://placehold.co/1600x500/e4000f/ffffff?text=Nintendo+Switch',
        'link'     => '/store?platform=Nintendo+eShop',
    ],
];
