import { useState } from 'react'
import { Link, useLocation, useParams } from 'react-router-dom'
import { api } from '../api/client'
import type { Order } from '../api/types'
import { formatPrice } from '../lib/format'
import { useApi } from '../lib/useApi'
import './account.css'

const formatDateTime = (iso: string) =>
  new Date(iso).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })

/** Order history (sign-in required): every order of the signed-in user, newest first, with its keys. */
export function OrdersPage() {
  const { state, reload } = useApi(api.orders, [])

  return (
    <div className="container page-content">
      <h1 className="page-title">Order history</h1>

      {state.status === 'loading' && <div className="cart-loading" aria-busy="true" />}
      {state.status === 'error' && <LoadError message={state.message} onRetry={reload} />}
      {state.status === 'ok' && state.data.length === 0 && (
        <div className="notice">
          <h2>No orders yet</h2>
          <Link to="/store" className="button button-primary">
            Browse the store
          </Link>
        </div>
      )}
      {state.status === 'ok' && (
        <div className="orders">
          {state.data.map((order) => (
            <OrderCard key={order.id} order={order} />
          ))}
        </div>
      )}
    </div>
  )
}

/** One order (sign-in required). Shown right after paying, as the confirmation with the keys. */
export function OrderPage() {
  const { id = '' } = useParams()
  const location = useLocation()
  const justBought = (location.state as { justBought?: boolean } | null)?.justBought === true
  const { state, reload } = useApi(() => api.order(Number(id)), [id])

  return (
    <div className="container page-content">
      <h1 className="page-title">{justBought ? 'Thank you for your purchase!' : `Order #${id}`}</h1>
      {justBought && <p className="order-thanks">Payment successful. Your keys are below and stay in your order history.</p>}

      {state.status === 'loading' && <div className="cart-loading" aria-busy="true" />}
      {state.status === 'error' && <LoadError message={state.message} onRetry={reload} />}
      {state.status === 'ok' && (
        <div className="orders">
          <OrderCard order={state.data} />
        </div>
      )}

      <p className="order-links">
        <Link to="/orders">All orders</Link> · <Link to="/store">Continue shopping</Link>
      </p>
    </div>
  )
}

function OrderCard({ order }: { order: Order }) {
  return (
    <article className="order">
      <header className="order-header">
        <div>
          <strong>Order #{order.id}</strong>
          <span>{formatDateTime(order.created_at)}</span>
        </div>
        <span className={`order-status order-status-${order.status}`}>{order.status}</span>
        <strong className="order-total">{formatPrice(order.total_cents)}</strong>
      </header>

      <ul className="order-items">
        {order.items.map((item) => (
          <li key={item.offer_id} className="order-item">
            <div className="cart-line-cover">
              {item.product.cover_image_url && <img src={item.product.cover_image_url} alt="" />}
            </div>
            <div className="order-item-info">
              <Link to={`/product/${item.product.slug}`}>
                <strong>{item.product.title}</strong>
              </Link>
              <span>
                Sold by {item.seller_name} · {item.quantity} × {formatPrice(item.unit_price_cents)}
              </span>
              <ul className="order-keys">
                {item.keys.map((key) => (
                  <GameKey key={key} code={key} />
                ))}
              </ul>
            </div>
          </li>
        ))}
      </ul>
    </article>
  )
}

function GameKey({ code }: { code: string }) {
  const [copied, setCopied] = useState(false)

  async function copy() {
    try {
      await navigator.clipboard.writeText(code)
      setCopied(true)
      window.setTimeout(() => setCopied(false), 2000)
    } catch {
      // Clipboard access can be refused; the key is still visible to copy by hand.
    }
  }

  return (
    <li className="order-key">
      <code>{code}</code>
      <button type="button" onClick={copy}>
        {copied ? 'Copied' : 'Copy'}
      </button>
    </li>
  )
}

function LoadError({ message, onRetry }: { message: string; onRetry: () => void }) {
  return (
    <div className="notice" role="alert">
      <p>{message}</p>
      <button type="button" className="button button-ghost" onClick={onRetry}>
        Try again
      </button>
    </div>
  )
}
