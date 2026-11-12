# Backend

A JSON API in plain PHP (no framework, no Composer packages) on PostgreSQL.

## Run locally

From the repo root:

```
php -S localhost:8000 -t backend/public backend/public/index.php
```

Then open http://localhost:8000/api/health. Settings come from `backend/.env`; see `.env.example` for the variables.

## Deploy to Render

Render builds `backend/Dockerfile` on its own servers; Docker is not needed locally. The image is PHP 8.4 with Apache, serving only `public/`.

1. In the Render Dashboard: **New > Web Service**, choose **Git Provider** and pick this repository.
2. Fill in the form:
   - **Language:** Docker
   - **Branch:** `master`
   - **Region:** the one closest to the Neon project's region
   - **Root Directory:** `backend` (if the form asks for a Dockerfile path and build context instead, use `backend/Dockerfile` and `backend`)
   - **Instance type:** Free
3. Under **Advanced**, set **Health Check Path** to `/api/health` and add the environment variables:

   | Variable | Value |
   |---|---|
   | `DATABASE_URL` | the pooled Neon connection string (same as in `backend/.env`) |
   | `APP_SECRET` | a new random value of at least 32 characters (Render's "Generate" button works) |
   | `FRONTEND_ORIGINS` | the frontend's address, e.g. `https://your-app.vercel.app`; several are comma-separated |

4. Click **Create Web Service**. When the deploy finishes, open `https://<service-name>.onrender.com/api/health`.

Every push to the chosen branch redeploys. On the free plan the service sleeps after 15 minutes without traffic and takes about a minute to wake.

`.github/workflows/backend-docker.yml` builds the same image on GitHub for every backend change and tests the running container, so a broken Dockerfile is caught before Render tries it.

## CORS

Browsers only let a web page read answers from an API on another address if the API names that page's origin as allowed. The API allows exactly the origins listed in `FRONTEND_ORIGINS` and no others. This is enforced by browsers; it does not block non-browser tools, so private data is protected by the sign-in session, not by CORS. Since the deployed frontend reaches the API through Vercel's forwarding, its requests are same-site and do not depend on this list; the list decides what other origins may do, which is nothing.

## Layout

| Path | What it does |
|---|---|
| `public/index.php` | The single entry point. Lists every route. |
| `src/Router.php` | Matches an address such as `/api/products/{slug}` to a controller function. |
| `src/Http.php` | Reads the request (JSON body, query string, token) and sends JSON; handles CORS. |
| `src/ApiError.php` | An error with an HTTP status and a message for the user. |
| `src/Config.php` | Reads settings from environment variables or `backend/.env`. |
| `src/Database.php` | Opens the database connection and runs queries. |
| `src/Auth.php` | Creates and checks the sign-in session cookie. |
| `src/Catalog.php` | Queries shared by several controllers: product cards and cart lines. |
| `src/Controllers/` | One file per area: home, products, auth, cart, orders. |

## Endpoints

All responses are JSON. Prices are whole numbers of euro cents (`1999` = €19.99).

| Method | Address | Sign-in | Purpose |
|---|---|---|---|
| GET | `/api/health` | no | Check that the API is running. |
| GET | `/api/home/banners` | no | Homepage banner block. |
| GET | `/api/home/best-sellers` | no | Homepage best sellers block (most copies sold). |
| GET | `/api/home/deals` | no | Homepage deals block (biggest discounts). |
| GET | `/api/products` | no | Store list with search, filters, sorting and paging. |
| GET | `/api/products/filters` | no | Values for the filter panel: platforms, regions, price range. |
| GET | `/api/products/{slug}` | no | One product with every seller's offer. |
| POST | `/api/auth/register` | no | Create an account and sign in. |
| POST | `/api/auth/login` | no | Sign in. |
| POST | `/api/auth/logout` | no | Sign out. |
| GET | `/api/auth/me` | no | The signed-in user, or `null` when nobody is signed in. |
| POST | `/api/cart/quote` | no | Current prices and stock for the cart's items. |
| POST | `/api/orders` | yes | Checkout: creates a paid order and returns the keys. |
| GET | `/api/orders` | yes | Order history with keys. |
| GET | `/api/orders/{id}` | yes | One order. |

### Store list parameters

`GET /api/products?search=eldn+ring&platform=Steam,GOG&region=GLOBAL&min_price=500&max_price=3000&on_sale=1&sort=price_asc&page=1&per_page=12`

- `search` tolerates typos ("cyberpnuk" finds Cyberpunk 2077).
- `platform`, `region`: one or more values, comma-separated.
- `min_price`, `max_price`: in cents.
- `sort`: `relevance` (default when searching), `newest` (default otherwise), `price_asc`, `price_desc`, `discount`, `title`.
- The response has `items`, `total`, `page`, `per_page`, `total_pages`.

### Signing in

`register` and `login` return `{"user": {...}}` and set a cookie named `session`. The cookie holds a signed token (JWT) valid for 7 days and is marked `HttpOnly`, so page scripts cannot read it; the browser sends it back automatically on later requests. `logout` deletes it.

Cookies only work when the browser sees the API as part of the same site. The deployed frontend therefore calls `/api/...` on its own address and Vercel forwards those requests here (see `frontend/vercel.json`); during development Vite does the same.

All POST requests must have `Content-Type: application/json`, otherwise the API answers 415.

### Request bodies

```
POST /api/auth/register   {"email": "...", "password": "...", "display_name": "..."}
POST /api/auth/login      {"email": "...", "password": "..."}
POST /api/cart/quote      {"items": [{"offer_id": 12, "quantity": 1}]}
POST /api/orders          {"items": [{"offer_id": 12, "quantity": 1}], "expected_total_cents": 3499}
```

`expected_total_cents` is optional: if it is sent and differs from the current total, checkout stops with a "prices have changed" error.

### Errors

Every error has the same shape, with a matching HTTP status:

```
{"error": {"message": "Please correct the highlighted fields.", "fields": {"email": "Enter a valid email address."}}}
```

| Status | Meaning |
|---|---|
| 400 | The request body is not valid JSON. |
| 401 | Not signed in, or the session is invalid or expired. |
| 415 | A POST request was not sent as JSON. |
| 404 | No such address, product or order. |
| 409 | Conflict: email already registered, not enough keys, price changed. |
| 422 | Input failed validation; `fields` says which. |
| 500 | Server problem; details are logged on the server only. |
