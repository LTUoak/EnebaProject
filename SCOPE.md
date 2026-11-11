# Project Scope

A marketplace web application for digital game keys in the style of eneba.com.

Constraints behind every choice below: one developer, about one week alongside university classes, and an assignment that prefers a few features done well over many shallow ones.

Stack: React + TypeScript frontend, PHP backend, PostgreSQL on Neon, deployed on the Render and Vercel free tiers.

## 1. What we build (in scope)

| Feature | Reason |
|---|---|
| Store page with filters (platform, region, price), sorting and search | Assignment: required feature. |
| Homepage built from three separate blocks (banner, best sellers, deals), each loading from its own backend endpoint | Assignment: required feature, including the one-source-per-block structure; three blocks are enough to show it well in the time available. |
| Search that tolerates typos | Assignment: required feature; done in the database with `pg_trgm`, so no separate search service is needed. |
| Product page listing offers from several sellers | Assignment: required feature and the core idea of a marketplace. |
| Cart | Assignment: required feature; kept in the browser to save backend work (see decisions log). |
| Simulated checkout that delivers a digital key | Assignment: required feature; payment is simulated because real payments are not asked for. |
| Sign-up and sign-in, with checkout and order history as protected pages | Assignment: required feature. |
| Order history showing purchased keys | Assignment: named as a protected page, and it is where the delivered key stays visible. |
| Eneba-style visual design | Assignment: required; the app should match eneba.com's look. |
| Seed data: products, sellers, offers, fake keys, banners and a few past orders | Technical: the store needs content, nobody can add it through the UI (see out of scope), and best sellers needs past sales to rank. |

## 2. What we deliberately skip (out of scope)

| Skipped | Reason |
|---|---|
| Real payments (card processor, PayPal) | Assignment asks for a simulated checkout; a real integration would take days of the one week. |
| Seller dashboard (creating offers, uploading keys) | Time: not a required feature; sellers and their offers come from seed data. |
| Admin panel | Time: not required; content is managed through seed files. |
| Emails (verification, password reset, key delivery by email) | Technical: the free tiers include no mail service; the key is shown on screen and in order history instead. |
| Social sign-in (Google, Steam, etc.) | Time: email and password already meets the sign-in requirement. |
| Server-side cart shared across devices | Time: a browser cart meets the requirement without an extra table and endpoints. |
| Multiple currencies and languages | Time: one currency (EUR) and one language keep every feature simpler. |
| Reviews, seller ratings, wishlist, cashback, loyalty features | Assignment: prefers few features done well; none of these are required. |
| Real game data from an external API | Technical: adds a dependency and rate limits; seed data is predictable for a demo. |
| Refunds, order cancellation, key revocation | Time: the purchase flow ends at key delivery, which is what the assignment asks for. |
| Eneba pages outside the purchase flow (blog, support, gift cards, legal pages) | Time: styling effort goes to the pages the required features live on. |

## 3. Decisions log

| Date | Decision | Reason |
|---|---|---|
| 2026-11-10 | Monorepo with `frontend/`, `backend/`, `database/` | Time: one repository is simpler for one developer to run and deploy. |
| 2026-11-10 | React + TypeScript (Vite) for the frontend | Assignment stack. |
| 2026-11-10 | PHP for the backend API | Assignment: PHP is an allowed backend language, and it is well documented, which saves time when working alone. |
| 2026-11-10 | PostgreSQL hosted on Neon | Technical: Neon has a free tier and Postgres provides `pg_trgm` for typo-tolerant search. |
| 2026-11-10 | Backend on Render (Docker), frontend on Vercel | Technical: Vercel has no official PHP support; Render's free tier runs any Dockerfile. |
| 2026-11-10 | Database schema as numbered plain-SQL migrations | Time: no migration framework to learn; files run in order and are easy to explain. |
| 2026-11-10 | Small PHP migration runner that records applied files in a `schema_migrations` table | Technical: each migration must run exactly once, and rerunning the script has to be safe. |
| 2026-11-10 | Prices live on offers, not products | Assignment: several sellers offer the same product at different prices. |
| 2026-11-10 | Prices stored as integer euro cents | Technical: decimal fractions in floating point cause rounding errors with money. |
| 2026-11-10 | Sellers are users with `is_seller = true`, no sellers table | Time: avoids an extra table while offers still point to a real seller. |
| 2026-11-10 | Added an `order_items` table | Technical: an order can hold several offers, and the price paid must be frozen at checkout. |
| 2026-11-10 | Stock is counted from unsold keys, not stored as a number | Technical: a stored counter can drift from the keys actually available. |
| 2026-11-10 | Cart kept in the browser (localStorage) | Time: guests can fill a cart before signing in, with no cart table or endpoints. |
| 2026-11-10 | Added a `banners` table for the homepage banner block | Assignment: every homepage block must get its content from the backend, and no existing table holds banner content. |
| 2026-11-10 | Added `old_price_cents` to offers for the deals block | Technical: a discount cannot be shown or ranked without knowing the price before it. |
| 2026-11-10 | Best sellers ranked from paid orders, with no new table or counter | Technical: the sales data already exists in orders and order items, and a stored counter could drift from it. |
| 2026-11-11 | Seed data written as PHP arrays loaded by a PHP script, not as SQL files | Technical: passwords must be hashed by PHP, and hundreds of keys and past orders are generated instead of typed by hand. |
| 2026-11-11 | Seeder only fills an empty database unless run with `--reset` | Technical: rerunning it by accident must not duplicate data or delete real sign-ups and orders. |
| 2026-11-11 | Demo account password kept in `backend/.env`, not in the repository | Technical: the repository is pushed to GitHub, so no working password should be committed. |
| 2026-11-11 | API written in plain PHP with a small router, no framework | Time: about 14 endpoints do not need one, and less code to learn means every line can be explained; it also needs no extra install step on Render. |
| 2026-11-11 | REST-style JSON API with one endpoint per homepage block | Assignment: each homepage block must load from its own backend source. |
| 2026-11-11 | Sign-in with signed tokens (JWT) sent in a header, not cookie sessions | Technical: frontend (Vercel) and backend (Render) are on different domains, where browsers restrict cookies; tokens also need no sessions table. |
| 2026-11-11 | Checkout runs in one database transaction and always recalculates prices on the server | Technical: a key must never be delivered twice or without an order, and prices sent by a browser cannot be trusted. |
| 2026-11-11 | Simulated payment always succeeds | Time: the assignment asks for a simulated checkout; a failure path would add screens without showing more. |
| 2026-11-11 | Backend packaged as a Docker image (official PHP 8.4 + Apache) | Technical: Render has no built-in PHP support but runs any Dockerfile; Apache handles several requests at once, unlike PHP's built-in test server. |
| 2026-11-11 | Docker image built and tested by GitHub Actions instead of locally | Technical: Docker is not installed on the development machine; GitHub builds the same Dockerfile for free and tests the running container. |
| 2026-11-11 | CORS allows only the origins listed in `FRONTEND_ORIGINS` | Technical: only the project's own frontend should be able to call the API from a browser; a setting avoids hard-coding the Vercel address. |
| 2026-11-11 | Frontend uses plain CSS with shared variables, no UI library | Assignment: matching Eneba's look is easier with own styles than by overriding a library's. |
| 2026-11-11 | `react-router-dom` is the only added frontend package; cart and sign-in state use React's built-in tools | Time: fewer libraries to learn and explain; the app's shared state is small. |
| 2026-11-11 | All backend calls go through one typed API client | Technical: the backend address, sign-in token and error handling live in one place instead of in every page. |
| 2026-11-11 | Frontend deployed to Vercel before the pages are built | Time: proves the Vercel to Render to Neon chain early, when problems are cheap to find. |
| 2026-11-11 | Store has its own name (KeyMarket), not Eneba's name or logo | Assignment: the task is to match Eneba's style, not to copy its trademark. |
| 2026-11-11 | Placeholder images for covers and banners | Time: choosing and hosting real artwork is separate work; placeholders let every page be built first. |
