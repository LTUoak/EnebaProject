import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { api } from '../api/client'
import type { Offer } from '../api/types'
import { useCart } from '../lib/cart-context'
import { formatPrice } from '../lib/format'
import { useApi } from '../lib/useApi'
import './product.css'

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })

/**
 * One product with every seller's offer. The cheapest offer that is in stock
 * is highlighted at the top; the full list sits below so the buyer can pick
 * another seller.
 */
export function ProductPage() {
  const { slug = '' } = useParams()
  const navigate = useNavigate()
  const cart = useCart()
  const { state, reload } = useApi(() => api.product(slug), [slug])
  /** The offer most recently added, to confirm the click next to it. */
  const [addedOfferId, setAddedOfferId] = useState<number | null>(null)

  if (state.status === 'loading') {
    return <div className="container page-content product-loading" aria-busy="true" />
  }
  if (state.status === 'error') {
    return (
      <div className="container page-content">
        <div className="notice" role="alert">
          <h2>{state.message}</h2>
          <button type="button" className="button button-ghost" onClick={reload}>
            Try again
          </button>
          <Link to="/store" className="button button-primary">
            Back to the store
          </Link>
        </div>
      </div>
    )
  }

  const product = state.data
  // The API sorts offers: in stock first, then cheapest. So the first one is the best buy.
  const best = product.offers.find((offer) => offer.in_stock)

  function addToCart(offer: Offer) {
    cart.add(offer.id)
    setAddedOfferId(offer.id)
  }

  function buyNow(offer: Offer) {
    cart.add(offer.id)
    navigate('/cart')
  }

  return (
    <div className="container page-content">
      <div className="product">
        <div className="product-cover">
          {product.cover_image_url && <img src={product.cover_image_url} alt={`${product.title} cover`} />}
        </div>

        <div className="product-info">
          <h1>{product.title}</h1>

          <ul className="product-facts">
            <li>
              <span>Platform</span>
              <strong>{product.platform}</strong>
            </li>
            <li>
              <span>Works in</span>
              <strong className="product-region">{product.region}</strong>
            </li>
            {product.release_date && (
              <li>
                <span>Released</span>
                <strong>{formatDate(product.release_date)}</strong>
              </li>
            )}
            <li>
              <span>Delivery</span>
              <strong>Digital key, instantly</strong>
            </li>
          </ul>

          {product.description && <p className="product-description">{product.description}</p>}
        </div>

        <aside className="product-buy" aria-label="Best offer">
          {best ? (
            <>
              <p className="product-buy-seller">
                Best price, sold by <strong>{best.seller_name}</strong>
              </p>
              {best.old_price_cents !== null && (
                <p className="product-buy-old">
                  <s>{formatPrice(best.old_price_cents)}</s>
                  <span className="product-discount">-{best.discount_percent}%</span>
                </p>
              )}
              <p className="product-buy-price">{formatPrice(best.price_cents)}</p>
              <button type="button" className="button button-primary product-buy-button" onClick={() => buyNow(best)}>
                Buy now
              </button>
              <button type="button" className="button button-ghost product-buy-button" onClick={() => addToCart(best)}>
                Add to cart
              </button>
              {addedOfferId === best.id && <AddedNote />}
            </>
          ) : (
            <>
              <p className="product-buy-price">Sold out</p>
              <p className="product-buy-seller">No seller has keys for this product right now.</p>
            </>
          )}
        </aside>
      </div>

      <section className="offers">
        <h2>
          {product.offers.length} {product.offers.length === 1 ? 'offer' : 'offers'} for this product
        </h2>
        <ul>
          {product.offers.map((offer) => (
            <li key={offer.id} className={offer.in_stock ? 'offer' : 'offer offer-sold-out'}>
              <div className="offer-seller">
                <strong>{offer.seller_name}</strong>
                <span>{offer.in_stock ? `${offer.stock} in stock` : 'Sold out'}</span>
              </div>
              <div className="offer-price">
                {offer.old_price_cents !== null && <s>{formatPrice(offer.old_price_cents)}</s>}
                <strong>{formatPrice(offer.price_cents)}</strong>
              </div>
              <div className="offer-action">
                <button
                  type="button"
                  className="button button-primary"
                  disabled={!offer.in_stock}
                  onClick={() => addToCart(offer)}
                >
                  Add to cart
                </button>
                {addedOfferId === offer.id && offer.id !== best?.id && <AddedNote />}
              </div>
            </li>
          ))}
        </ul>
      </section>
    </div>
  )
}

function AddedNote() {
  return (
    <p className="product-added" role="status">
      Added to cart. <Link to="/cart">View cart</Link>
    </p>
  )
}
