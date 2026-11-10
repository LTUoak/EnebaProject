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
