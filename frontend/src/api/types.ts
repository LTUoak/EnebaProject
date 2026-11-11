// The shapes of the JSON the backend sends. They mirror backend/README.md.
// All prices are whole numbers of euro cents (1999 = €19.99).

/** A product as shown on a store tile. */
export interface ProductCard {
  id: number
  slug: string
  title: string
  cover_image_url: string | null
  platform: string
  region: string
  release_date: string | null
  price_cents: number
  old_price_cents: number | null
  discount_percent: number | null
  in_stock: boolean
  offer_count: number
  best_offer_id: number
}

export interface Banner {
  id: number
  title: string
  subtitle: string
  image_url: string
  link_url: string
}

export type ProductSort = 'relevance' | 'newest' | 'price_asc' | 'price_desc' | 'discount' | 'title'

/** What the store page can ask for. Every field is optional. */
export interface ProductQuery {
  search?: string
  platform?: string[]
  region?: string[]
  min_price?: number
  max_price?: number
  on_sale?: boolean
  sort?: ProductSort
  page?: number
  per_page?: number
}

export interface ProductList {
  items: ProductCard[]
  total: number
  page: number
  per_page: number
  total_pages: number
  sort: ProductSort
}

export interface FilterOption {
  value: string
  count: number
}

export interface ProductFilters {
  platforms: FilterOption[]
  regions: FilterOption[]
  price: { min_cents: number; max_cents: number }
  sorts: ProductSort[]
}

/** One seller's offer on a product page. */
export interface Offer {
  id: number
  seller_name: string
  price_cents: number
  old_price_cents: number | null
  discount_percent: number | null
  stock: number
  in_stock: boolean
}

export interface ProductDetail {
  id: number
  slug: string
  title: string
  description: string
  cover_image_url: string | null
  platform: string
  region: string
  release_date: string | null
  offers: Offer[]
}

export interface User {
  id: number
  email: string
  display_name: string
}

export interface AuthResult {
  token: string
  user: User
}

/** What the browser's cart stores and sends: which offer, how many. Never prices. */
export interface CartItem {
  offer_id: number
  quantity: number
}

export interface ProductSummary {
  slug: string
  title: string
  cover_image_url: string | null
  platform: string
  region: string
}

/** A cart line priced by the server. Unavailable offers carry no price details. */
export type CartLine =
  | { offer_id: number; quantity: number; status: 'unavailable' }
  | {
      offer_id: number
      quantity: number
      status: 'ok' | 'insufficient_stock'
      stock: number
      unit_price_cents: number
      old_price_cents: number | null
      line_total_cents: number
      seller_name: string
      product: ProductSummary
    }

export interface CartQuote {
  items: CartLine[]
  total_cents: number
  can_checkout: boolean
}

export interface OrderItem {
  offer_id: number
  quantity: number
  unit_price_cents: number
  line_total_cents: number
  seller_name: string
  product: ProductSummary
  keys: string[]
}

export interface Order {
  id: number
  status: 'pending' | 'paid' | 'failed'
  total_cents: number
  created_at: string
  paid_at: string | null
  items: OrderItem[]
}
