<?php
/**
 * Seed data: users.
 *
 * Sellers own the offers. Nobody signs in as a seller (there is no seller
 * dashboard), so the seeder gives them a random password that is thrown away.
 *
 * Buyers own the past orders. Only the demo buyer can sign in; its password
 * is SEED_DEMO_PASSWORD in backend/.env (created by the seeder if missing).
 *
 * All addresses use example.com, a domain reserved for examples.
 */

return [
    'sellers' => [
        ['email' => 'keyharbor@example.com',   'display_name' => 'KeyHarbor'],
        ['email' => 'pixelvault@example.com',  'display_name' => 'PixelVault'],
        ['email' => 'nordickeys@example.com',  'display_name' => 'NordicKeys'],
        ['email' => 'gamelantern@example.com', 'display_name' => 'GameLantern'],
        ['email' => 'turbocodes@example.com',  'display_name' => 'TurboCodes'],
    ],

    'buyers' => [
        ['email' => 'demo@example.com',  'display_name' => 'Demo Buyer', 'can_sign_in' => true],
        ['email' => 'ruta@example.com',  'display_name' => 'Ruta'],
        ['email' => 'jonas@example.com', 'display_name' => 'Jonas'],
        ['email' => 'egle@example.com',  'display_name' => 'Egle'],
        ['email' => 'tomas@example.com', 'display_name' => 'Tomas'],
    ],
];
