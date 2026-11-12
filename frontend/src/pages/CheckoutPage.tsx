import { useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import { useAuth } from '../lib/auth-context'
import { useCart } from '../lib/cart-context'
import { formatPrice } from '../lib/format'
import { useApi } from '../lib/useApi'
import './account.css'

/**
 * Checkout (sign-in required). Shows what will be bought at current prices
 * and a "Pay" button. Payment is simulated: no card details are asked for.
 * The backend creates the order and hands over the keys in one step.
 */
export function CheckoutPage() {
  const cart = useCart()
  const { user } = useAuth()
  const navigate = useNavigate()
  const { state, reload } = useApi(
    // Once the order is placed the cart is empty and there is nothing left to price.
    () =>
      cart.items.length > 0
        ? api.cartQuote(cart.items)
        : Promise.resolve({ items: [], total_cents: 0, can_checkout: false }),
    [JSON.stringify(cart.items)],
  )
  const [paying, setPaying] = useState(false)
  const [error, setError] = useState('')

  // `paying` keeps this page up while the finished order page opens (the cart is emptied just before).
  if (cart.items.length === 0 && !paying) {
    return <Navigate to="/cart" replace />
  }

  async function pay(totalCents: number) {
    setPaying(true)
    setError('')
    try {
      // The total the buyer saw is sent along; if a price changed meanwhile the backend refuses.
      const order = await api.createOrder(cart.items, totalCents)
      cart.clear()
      navigate(`/orders/${order.id}`, { replace: true, state: { justBought: true } })
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : 'Something went wrong.')
      setPaying(false)
      reload()
    }
  }

  return (
    <div className="container page-content">
      <h1 className="page-title">Checkout</h1>

      {state.status === 'loading' && <div className="cart-loading" aria-busy="true" />}

      {state.status === 'error' && (
        <div className="notice" role="alert">
          <p>Could not load your order: {state.message}</p>
          <button type="button" className="button button-ghost" onClick={reload}>
            Try again
          </button>
        </div>
      )}

      {state.status === 'ok' && (
        <div className="cart">
          <ul className="cart-lines">
            {state.data.items.map((line) =>
              line.status === 'unavailable' ? null : (
                <li key={line.offer_id} className="cart-line">
                  <div className="cart-line-cover">
                    {line.product.cover_image_url && <img src={line.product.cover_image_url} alt="" />}
                  </div>
                  <div className="cart-line-info">
                    <strong>{line.product.title}</strong>
                    <span>
                      Sold by {line.seller_name} · {line.quantity} × {formatPrice(line.unit_price_cents)}
                    </span>
                  </div>
                  <strong className="cart-line-total">{formatPrice(line.line_total_cents)}</strong>
                </li>
              ),
            )}
          </ul>

          <aside className="summary">
            <h2>Payment</h2>
            <p className="summary-note">
              Paying as <strong>{user?.email}</strong>. This is a demonstration: the payment is simulated, no money
              is taken and the keys are not real.
            </p>
            <p className="summary-total">
              <span>Total</span>
              <strong>{formatPrice(state.data.total_cents)}</strong>
            </p>

            {error && (
              <p className="form-error" role="alert">
                {error}
              </p>
            )}

            {state.data.can_checkout ? (
              <button
                type="button"
                className="button button-primary summary-button"
                disabled={paying}
                onClick={() => pay(state.data.total_cents)}
              >
                {paying ? 'Processing…' : `Pay ${formatPrice(state.data.total_cents)}`}
              </button>
            ) : (
              <p className="form-error">Some items are no longer available in the quantity you chose.</p>
            )}
            <Link to="/cart" className="summary-link">
              Back to cart
            </Link>
          </aside>
        </div>
      )}
    </div>
  )
}
