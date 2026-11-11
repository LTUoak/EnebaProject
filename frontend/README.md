# Frontend

React + TypeScript, built with Vite. Plain CSS with shared variables; the only added package is `react-router-dom` for page addresses.

## Run locally

```
cd frontend
npm install
npm run dev
```

Open http://localhost:5173. The app calls `/api/...` on its own address and the dev server forwards those requests to the backend, by default at `http://localhost:8000` (start it with the command in `backend/README.md`). To use the deployed backend instead, create `frontend/.env.local` containing:

```
API_PROXY_TARGET=https://enebaproject-ggfy.onrender.com
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
3. Click **Deploy**. No environment variables are needed.

`vercel.json` does three things:

- forwards `/api/...` to the backend on Render, so the browser sees the API as part of this site and the sign-in cookie works (the backend address is written in that file);
- sends every other address to `index.html`, so reloading a page such as `/product/...` works instead of giving a 404;
- adds security headers, including a Content Security Policy.

Every push to `master` redeploys.
