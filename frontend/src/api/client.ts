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

// Requests go to "/api/..." on the site's own address. A proxy forwards them to
// the backend: Vercel in production (vercel.json), Vite during development
// (vite.config.ts). Because of that the browser treats the API as part of this
// site, and the sign-in session can live in an HttpOnly cookie that JavaScript
// (including any malicious script) cannot read. This file never sees the session.

// The free backend sleeps when idle and needs up to a minute to wake. While it
// wakes, the proxy answers with a gateway error, so reads are retried for a while.
const RETRY_STATUSES = [502, 503, 504]
const RETRY_DELAYS_MS = [2000, 4000, 8000, 12000, 15000, 15000]

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

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
  const init: RequestInit =
    method === 'POST'
      ? { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body ?? {}) }
      : { method }

  let response: Response | undefined
  for (let attempt = 0; ; attempt++) {
    try {
      response = await fetch(path, init)
    } catch {
      response = undefined
    }
    // Only reads are retried. Repeating a POST could, for example, place an order twice.
    const failedToConnect = response === undefined || RETRY_STATUSES.includes(response.status)
    if (method !== 'GET' || !failedToConnect || attempt >= RETRY_DELAYS_MS.length) break
    await sleep(RETRY_DELAYS_MS[attempt])
  }
  if (response === undefined) {
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
  logout: () => request<{ signed_out: boolean }>('POST', '/api/auth/logout'),
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
