/// <reference types="vite/client" />

// Settings read from .env files at build time. Only names starting with VITE_
// reach the browser, and everything here is public: never put secrets in them.
interface ImportMetaEnv {
  /** Address of the backend API, e.g. https://enebaproject-ggfy.onrender.com */
  readonly VITE_API_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
