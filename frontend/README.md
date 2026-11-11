# Frontend

React + TypeScript, built with Vite. Plain CSS with shared variables; the only added package is `react-router-dom` for page addresses.

## Run locally

```
cd frontend
npm install
npm run dev
```

Open http://localhost:5173. The app calls the backend at `VITE_API_URL`; without that setting it expects the backend at `http://localhost:8000` (start it with the command in `backend/README.md`). To use the deployed backend instead, create `frontend/.env.local` containing:

```
VITE_API_URL=https://enebaproject-ggfy.onrender.com
```

Other commands: `npm run build` (type-check and build into `dist/`), `npm run lint`.

## Layout

| Path | What it does |
|---|---|
| `src/main.tsx` | Starts React. |
| `src/App.tsx` | Lists every page address (route). |
| `src/config.ts` | The store's name. |
| `src/index.css` | Design tokens (colours, spacing) and base styles. |
| `src/api/client.ts` | The only code that calls the backend: `api.deals()`, `api.products(...)`, and so on. |
| `src/api/types.ts` | TypeScript shapes of the backend's answers. |
| `src/components/` | Pieces shared by pages: `Layout`, `Header`, `Footer`. |
| `src/pages/` | One component per page. |

## Deploy to Vercel

1. In the Vercel dashboard: **Add New > Project**, import this repository.
2. Set **Root Directory** to `frontend`. Vercel detects Vite and fills in the build settings.
3. Under **Environment Variables**, add `VITE_API_URL` = `https://enebaproject-ggfy.onrender.com`.
4. Click **Deploy**.
5. On Render, add the new Vercel address to the backend's `FRONTEND_ORIGINS` variable (comma-separated, no trailing slash). Without this the browser blocks the frontend's requests.

`vercel.json` sends every address to `index.html`, so reloading a page such as `/product/...` works instead of giving a 404. Every push to `master` redeploys.
