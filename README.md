# Eneba-style game key marketplace

A marketplace web application for digital game keys, built as an assignment: *"implement a marketplace web application in Eneba's style that matches eneba.com"*. Several sellers offer the same game at different prices; a buyer picks an offer, checks out, and receives a key.

**This is a student project.** It imitates the look and structure of [eneba.com](https://www.eneba.com) for demonstration only. It is not the real Eneba store and is not affiliated with it. Payments are simulated and every game key is fake.

- **Live site:** https://eneba-project-six.vercel.app
- **Live API:** https://enebaproject-ggfy.onrender.com/api/health

The backend runs on a free plan that sleeps when idle, so the first page load after a pause can take up to a minute.

## Status

| Feature | State |
|---|---|
| Database schema, sample data | Done |
| Backend API (all endpoints) | Done |
| Homepage built from separate blocks (deals banner, categories, best sellers, top deals) | Done |
| Store page: search with typo tolerance, filters, sorting, paging | Done |
| Product page with offers from several sellers | Done |
| Cart and simulated checkout that delivers a key | Done |
| Sign-up, sign-in, protected checkout and order history | Done |

## How it is built

```
frontend/   React + TypeScript (Vite), deployed on Vercel
backend/    JSON API in plain PHP, deployed on Render as a Docker image
database/   PostgreSQL schema (SQL migrations) and sample data, hosted on Neon
```

```
Browser ──> Vercel (React app)
               │  /api/* is forwarded by Vercel
               ▼
            Render (PHP API) ──> Neon (PostgreSQL)
```

- **Frontend:** React with TypeScript, plain CSS with shared variables, `react-router-dom` for page addresses. All backend calls go through one typed client (`frontend/src/api/client.ts`).
- **Backend:** plain PHP 8.4 without a framework: a small router, one controller per area, PDO for the database. About 15 endpoints did not justify a framework, and every line can be explained.
- **Database:** eight numbered SQL migrations and a seed script, each run by a small PHP runner.
- **Homepage blocks:** each block (banner, best sellers, deals) loads from its own endpoint and handles its own loading and error state.
- **Typo-tolerant search:** PostgreSQL's `pg_trgm` extension compares text by three-letter fragments, so "cyberpnuk" finds Cyberpunk 2077.

More detail lives next to the code:

- [`SCOPE.md`](SCOPE.md): what is built, what is deliberately left out, and a log of every decision with its reason.
- [`backend/README.md`](backend/README.md): the API reference and Render deployment.
- [`frontend/README.md`](frontend/README.md): frontend layout and Vercel deployment.
- [`database/README.md`](database/README.md): migrations and sample data.
- [`chat-log.md`](chat-log.md): the full conversation with the AI assistant used to build the project.

## Data model in one paragraph

A **product** is one thing you can buy a key for, such as "Elden Ring (PC) Steam Key GLOBAL"; platform and region belong to the product. An **offer** is one seller's price for a product, which is why prices live on offers. Each offer has **game keys** in stock. An **order** has **order items** (one per offer bought, with the price frozen at purchase), and the keys the buyer receives point at their order item. Sellers are ordinary **users** flagged as sellers. Prices are whole numbers of euro cents.

## Security measures

- Passwords are stored only as hashes (PHP `password_hash`).
- The sign-in session is a signed token kept in an `HttpOnly` cookie, which page scripts cannot read. The frontend reaches the API through its own address so the cookie is first-party.
- The session cookie is `SameSite=Lax` and the API accepts POST requests only as JSON, which blocks requests forged by other websites.
- Prices and stock are always read from the database at checkout; nothing the browser sends about prices is trusted.
- Checkout runs in a single database transaction, so a key cannot be delivered twice or without an order.
- The API allows browser requests only from listed origins (CORS), and the site sends a Content Security Policy.
- Secrets (database address, signing secret) live only in environment variables and an untracked `.env` file; none are in this repository.

## Known limitations

- **Slow first load:** the free Render service sleeps after 15 minutes without traffic.
- **Payment is simulated** and always succeeds; there is no failed-payment path.
- **No limit on sign-in attempts,** so password guessing is not slowed down.
- **Search handles typos, not abbreviations:** "gta" does not find Grand Theft Auto V.
- **No seller or admin screens:** sellers, offers and keys come from the seed data.
- **No emails:** keys are shown on screen and in order history.
- **One currency (EUR) and one language.**
- **Artwork is linked from the publishers' servers** (mostly Steam's image servers), not hosted here, and belongs to its publishers.
- **Prices, sellers and sales figures are invented** for the demonstration.

`SCOPE.md` gives the reason for each of these.

## Run it locally

Requirements: PHP 8.4 with the `pdo_pgsql` extension, Node.js 20 or newer, and a PostgreSQL database (a free Neon project works).

1. Copy `backend/.env.example` to `backend/.env` and fill in the values. This file is ignored by git; never commit it.
2. Create the tables and sample data:
   ```
   php database/migrate.php
   php database/seed.php
   ```
3. Start the backend:
   ```
   php -S localhost:8000 -t backend/public backend/public/index.php
   ```
4. In a second terminal, start the frontend:
   ```
   cd frontend
   npm install
   npm run dev
   ```
5. Open http://localhost:5173.

## How it was made

The project was built by one student in about a week, working with an AI coding assistant (Claude Code). The student set the requirements, constraints and priorities and reviewed each step; the assistant wrote code, explained it and tested it. The whole conversation is kept in [`chat-log.md`](chat-log.md), and each decision is recorded with its reason in [`SCOPE.md`](SCOPE.md).
