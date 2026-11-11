<?php
/**
 * Seed data: the homepage promo strip.
 *
 * The first banner is the large advert; the rest are the narrow tiles beside
 * it. They advertise deals in general (not one game) and each links to the
 * store with filters applied.
 *
 * 'link' is either a plain URL path, or ['product' => [<game>, <device>,
 * <platform>, <region>]] to link to one product's page.
 * 'hero' names a game from images.php whose wide artwork is the backdrop.
 */

return [
    [
        'title'    => 'Autumn Deals',
        'subtitle' => 'Save up to 75% on top games',
        'hero'     => 'Cyberpunk 2077',
        'link'     => '/store?on_sale=1&sort=discount',
    ],
    [
        'title'    => 'Steam keys',
        'subtitle' => 'Cheapest sellers first',
        'hero'     => 'Elden Ring',
        'link'     => '/store?platform=Steam&sort=price_asc',
    ],
    [
        'title'    => 'Under €10',
        'subtitle' => 'Great games for less',
        'hero'     => 'Hades',
        'link'     => '/store?max_price=1000&sort=price_asc',
    ],
    [
        'title'    => 'Console keys',
        'subtitle' => 'Xbox, PlayStation, Nintendo',
        'hero'     => 'God of War Ragnarök',
        'link'     => '/store?platform=Xbox%20Live,PSN,Nintendo%20eShop',
    ],
];
