<?php
/**
 * Seed data: artwork for each game, keyed by the 'game' name used in products.php.
 *
 * Most images are the publishers' official art served by Steam's image servers:
 * a Steam app ID is turned into a portrait cover (600x900) and a wide "hero"
 * image used by banners. Every platform/region version of a game shares its art.
 * The two Nintendo games are not on Steam, so they have full addresses instead.
 *
 * The images are linked, not copied into this repository. They belong to their
 * publishers and are used here only to illustrate a student project.
 */

$steamCover = fn (int $appId) => "https://cdn.cloudflare.steamstatic.com/steam/apps/$appId/library_600x900.jpg";
$steamHero  = fn (int $appId) => "https://cdn.cloudflare.steamstatic.com/steam/apps/$appId/library_hero.jpg";

$steamAppIds = [
    'Elden Ring'                       => 1245620,
    'Cyberpunk 2077'                   => 1091500,
    'Red Dead Redemption 2'            => 1174180,
    'Grand Theft Auto V'               => 271590,
    'The Witcher 3: Wild Hunt'         => 292030,
    "Baldur's Gate 3"                  => 1086940,
    'Hogwarts Legacy'                  => 990080,
    'Split Fiction'                    => 2001120,
    'EA SPORTS FC 25'                  => 2669320,
    'It Takes Two'                     => 1426210,
    'Forza Horizon 5'                  => 1551360,
    'Sea of Thieves'                   => 1172620,
    'God of War'                       => 1593500,
    'God of War Ragnarök'              => 2322010,
    "Marvel's Spider-Man Remastered"   => 1817070,
    'Helldivers 2'                     => 553850,
    'Stardew Valley'                   => 413150,
    'Hades'                            => 1145360,
    'Terraria'                         => 105600,
    'Hollow Knight'                    => 367520,
    'Palworld'                         => 1623730,
    'Black Myth: Wukong'               => 2358720,
    'Resident Evil 4'                  => 2050650,
    "Ghost of Tsushima Director's Cut" => 2215430,
];

$covers = array_map($steamCover, $steamAppIds);
$heroes = array_map($steamHero, $steamAppIds);

// Nintendo games (not on Steam)
$covers['Mario Kart 8 Deluxe'] = 'https://assets.nintendo.com/image/upload/c_fill,w_600,h_800/ncom/software/switch/70010000000153/de697f487a36d802dd9a5ff0341f717c8486221f2f1219b675af37aca63bc453';
$covers['The Legend of Zelda: Tears of the Kingdom'] = 'https://upload.wikimedia.org/wikipedia/en/f/fb/The_Legend_of_Zelda_Tears_of_the_Kingdom_cover.jpg';

return ['covers' => $covers, 'heroes' => $heroes];
