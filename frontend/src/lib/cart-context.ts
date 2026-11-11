import { createContext, useContext } from 'react'
import type { CartItem } from '../api/types'

/** The most copies of one offer a cart may hold (the backend enforces the same limit). */
export const MAX_QUANTITY = 10

export interface Cart {
  items: CartItem[]
  /** Total number of copies in the cart, for the badge on the cart icon. */
  count: number
  add: (offerId: number, quantity?: number) => void
  setQuantity: (offerId: number, quantity: number) => void
  remove: (offerId: number) => void
  clear: () => void
}

export const CartContext = createContext<Cart | null>(null)

/** Gives any component access to the cart. */
export function useCart(): Cart {
  const cart = useContext(CartContext)
  if (!cart) throw new Error('useCart must be used inside <CartProvider>')
  return cart
}
