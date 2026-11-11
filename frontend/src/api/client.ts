// The one place the frontend talks to the backend. Pages call api.something()
// and get typed data back; they never build addresses or headers themselves.

import type {
  AuthResult,
  Banner,
  CartItem,
  CartQuote,
  Order,
  ProductCard,
  ProductDetail,
  ProductFilters,
  ProductList,
  ProductQuery,
  User,
} from './types'

// Where the backend lives. Set VITE_API_URL per environment (see .env.example);
// without it the app expects the backend running locally.
const API_URL = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000').replace(/\/+$/, '')

const TOKEN_KEY = 'auth_token'

/** The sign-in token, kept in the browser so a page reload stays signed in. */
export const tokenStore = {
  get: (): string | null => localStorage.getItem(TOKEN_KEY),
  set: (token: string): void => localStorage.setItem(TOKEN_KEY, token),
  clear: (): void => localStorage.removeItem(TOKEN_KEY),
}

/**
 * Any failed request. `status` is the HTTP status (0 = the server could not be
 * reached), `message` is safe to show to the user, and `fields` maps form
 * fields to what is wrong with them.
 */
export class ApiError extends Error {
  status: number
  fields: Record<string, string>

  constructor(status: number, message: string, fields: Record<string, string> = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.fields = fields
  }
}

async function request<T>(method: 'GET' | 'POST', path: string, body?: unknown): Promise<T> {
  const headers: Record<string, string> = {}
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }
  const token = tokenStore.get()
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  let response: Response
  try {
    response = await fetch(API_URL + path, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    })
  } catch {
    throw new ApiError(0, 'Could not reach the server. Please check your connection and try again.')
  }

  const data = await response.json().catch(() => null)
  if (!response.ok) {
    throw new ApiError(
      response.status,
      data?.error?.message ?? 'Something went wrong. Please try again.',
      data?.error?.fields ?? {},
    )
  }
  return data as T
}

/** Turns { platform: ['Steam', 'GOG'], page: 2 } into "?platform=Steam%2CGOG&page=2". */
function toQueryString(query: ProductQuery): string {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(query)) {
    if (value === undefined || value === '' || value === false) continue
    if (Array.isArray(value)) {
      if (value.length > 0) params.set(key, value.join(','))
    } else if (value === true) {
      params.set(key, '1')
    } else {
      params.set(key, String(value))
    }
  }
  const text = params.toString()
  return text ? `?${text}` : ''
}

export const api = {
  // Homepage blocks: one endpoint each
  banners: () => request<{ items: Banner[] }>('GET', '/api/home/banners').then((r) => r.items),
  bestSellers: () => request<{ items: ProductCard[] }>('GET', '/api/home/best-sellers').then((r) => r.items),
  deals: () => request<{ items: ProductCard[] }>('GET', '/api/home/deals').then((r) => r.items),

  // Store and product page
  products: (query: ProductQuery = {}) => request<ProductList>('GET', '/api/products' + toQueryString(query)),
  productFilters: () => request<ProductFilters>('GET', '/api/products/filters'),
  product: (slug: string) =>
    request<{ product: ProductDetail }>('GET', `/api/products/${encodeURIComponent(slug)}`).then((r) => r.product),

  // Accounts
  register: (input: { email: string; password: string; display_name: string }) =>
    request<AuthResult>('POST', '/api/auth/register', input),
  login: (input: { email: string; password: string }) => request<AuthResult>('POST', '/api/auth/login', input),
  me: () => request<{ user: User }>('GET', '/api/auth/me').then((r) => r.user),

  // Cart and orders
  cartQuote: (items: CartItem[]) => request<CartQuote>('POST', '/api/cart/quote', { items }),
  createOrder: (items: CartItem[], expectedTotalCents?: number) =>
    request<{ order: Order }>('POST', '/api/orders', { items, expected_total_cents: expectedTotalCents }).then(
      (r) => r.order,
    ),
  orders: () => request<{ items: Order[] }>('GET', '/api/orders').then((r) => r.items),
  order: (id: number) => request<{ order: Order }>('GET', `/api/orders/${id}`).then((r) => r.order),
}
