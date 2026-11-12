import { Link } from 'react-router-dom'
import { api } from '../api/client'
import type { CartLine } from '../api/types'
import { MAX_QUANTITY, useCart } from '../lib/cart-context'
import { formatPrice } from '../lib/format'
import { useApi } from '../lib/useApi'
import './account.css'

/**
 * The cart. The browser only remembers which offers and how many; this page
 * asks the backend for current prices and stock every time the cart changes,
 * so it never shows a price the browser saved earlier.
 */
export function CartPage() {
  const cart = useCart()

  if (cart.items.length === 0) {
    return (
      <div className="container page-content">
        <h1 className="page-title">Cart</h1>
        <div className="notice">
          <h2>Your cart is empty</h2>
          <Link to="/store" className="button button-primary">
            Browse the store
          </Link>
        </div>
      </div>
    )
  }
  return <FilledCart />
}

function FilledCart() {
  const cart = useCart()
  const { state, reload } = useApi(() => api.cartQuote(cart.items), [JSON.stringify(cart.items)])

  return (
    <div className="container page-content">
      <h1 className="page-title">Cart</h1>

      {state.status === 'loading' && <div className="cart-loading" aria-busy="true" />}

      {state.status === 'error' && (
        <div className="notice" role="alert">
          <p>Could not load your cart: {state.message}</p>
          <button type="button" className="button button-ghost" onClick={reload}>
            Try again
          </button>
        </div>
      )}

      {state.status === 'ok' && (
        <div className="cart">
          <ul className="cart-lines">
            {state.data.items.map((line) => (
              <CartRow key={line.offer_id} line={line} />
            ))}
          </ul>

          <aside className="summary">
            <h2>Summary</h2>
            <p className="summary-total">
              <span>Total</span>
              <strong>{formatPrice(state.data.total_cents)}</strong>
            </p>
            {state.data.can_checkout ? (
              <Link to="/checkout" className="button button-primary summary-button">
                Proceed to checkout
              </Link>
            ) : (
              <p className="form-error">Fix the items marked above before checking out.</p>
            )}
            <Link to="/store" className="summary-link">
              Continue shopping
            </Link>
          </aside>
        </div>
      )}
    </div>
  )
}

function CartRow({ line }: { line: CartLine }) {
  const cart = useCart()

  if (line.status === 'unavailable') {
    return (
      <li className="cart-line">
        <div className="cart-line-info">
          <strong>This offer is no longer available</strong>
        </div>
        <button type="button" className="button button-ghost" onClick={() => cart.remove(line.offer_id)}>
          Remove
        </button>
      </li>
    )
  }

  return (
    <li className="cart-line">
      <Link to={`/product/${line.product.slug}`} className="cart-line-cover">
        {line.product.cover_image_url && <img src={line.product.cover_image_url} alt="" />}
      </Link>

      <div className="cart-line-info">
        <Link to={`/product/${line.product.slug}`}>
          <strong>{line.product.title}</strong>
        </Link>
        <span>Sold by {line.seller_name}</span>
        {line.status === 'insufficient_stock' && (
          <span className="form-error">
            {line.stock === 0 ? 'Sold out.' : `Only ${line.stock} left.`} Lower the quantity or remove it.
          </span>
        )}
      </div>

      <label className="cart-line-quantity">
        <span className="visually-hidden">Quantity</span>
        <select value={line.quantity} onChange={(e) => cart.setQuantity(line.offer_id, Number(e.target.value))}>
          {Array.from({ length: MAX_QUANTITY }, (_, i) => i + 1).map((n) => (
            <option key={n} value={n}>
              {n}
            </option>
          ))}
        </select>
      </label>

      <strong className="cart-line-total">{formatPrice(line.line_total_cents)}</strong>

      <button type="button" className="cart-line-remove" onClick={() => cart.remove(line.offer_id)}>
        Remove
      </button>
    </li>
  )
}
