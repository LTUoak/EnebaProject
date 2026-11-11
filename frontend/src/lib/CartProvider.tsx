import { useEffect, useMemo, useState, type ReactNode } from 'react'
import type { CartItem } from '../api/types'
import { CartContext, MAX_QUANTITY, type Cart } from './cart-context'

const STORAGE_KEY = 'cart'

/** Reads the saved cart, ignoring anything that is not a valid list of items. */
function loadCart(): CartItem[] {
  try {
    const saved: unknown = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]')
    if (!Array.isArray(saved)) return []
    return saved.filter(
      (item): item is CartItem =>
        Number.isInteger(item?.offer_id) &&
        Number.isInteger(item?.quantity) &&
        item.quantity >= 1 &&
        item.quantity <= MAX_QUANTITY,
    )
  } catch {
    return []
  }
}

/**
 * Holds the cart for the whole app and saves it in the browser, so it survives
 * reloads and works before signing in. Only offer ids and quantities are kept:
 * prices always come fresh from the backend.
 */
export function CartProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<CartItem[]>(loadCart)

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
  }, [items])

  const cart = useMemo<Cart>(() => {
    const clamp = (quantity: number) => Math.max(1, Math.min(MAX_QUANTITY, quantity))
    return {
      items,
      count: items.reduce((sum, item) => sum + item.quantity, 0),
      add: (offerId, quantity = 1) =>
        setItems((current) =>
          current.some((item) => item.offer_id === offerId)
            ? current.map((item) =>
                item.offer_id === offerId ? { ...item, quantity: clamp(item.quantity + quantity) } : item,
              )
            : [...current, { offer_id: offerId, quantity: clamp(quantity) }],
        ),
      setQuantity: (offerId, quantity) =>
        setItems((current) =>
          current.map((item) => (item.offer_id === offerId ? { ...item, quantity: clamp(quantity) } : item)),
        ),
      remove: (offerId) => setItems((current) => current.filter((item) => item.offer_id !== offerId)),
      clear: () => setItems([]),
    }
  }, [items])

  return <CartContext.Provider value={cart}>{children}</CartContext.Provider>
}
