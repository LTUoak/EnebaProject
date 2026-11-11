import { Link } from 'react-router-dom'
import type { ProductCard as ProductCardData } from '../api/types'
import { formatPrice } from '../lib/format'
import './ProductCard.css'

/** One store tile: cover, title, platform and region, and the cheapest price. Used on every product list. */
export function ProductCard({ product }: { product: ProductCardData }) {
  return (
    <Link to={`/product/${product.slug}`} className="product-card">
      <div className="product-card-cover">
        {product.cover_image_url && <img src={product.cover_image_url} alt="" loading="lazy" />}
        {product.discount_percent !== null && (
          <span className="product-card-discount">-{product.discount_percent}%</span>
        )}
        <span className="product-card-platform">{product.platform}</span>
      </div>

      <div className="product-card-body">
        <h3 className="product-card-title">{product.title}</h3>
        <p className="product-card-region">{product.region}</p>

        <div className="product-card-price">
          {product.in_stock ? (
            <>
              <span className="product-card-from">From</span>
              {product.old_price_cents !== null && (
                <s className="product-card-old-price">{formatPrice(product.old_price_cents)}</s>
              )}
              <strong>{formatPrice(product.price_cents)}</strong>
            </>
          ) : (
            <span className="product-card-sold-out">Sold out</span>
          )}
        </div>
      </div>
    </Link>
  )
}

/** A grey tile in the shape of a card, shown while the real ones load. */
export function ProductCardSkeleton() {
  return (
    <div className="product-card product-card-skeleton" aria-hidden="true">
      <div className="product-card-cover" />
      <div className="product-card-body">
        <div className="skeleton-line" />
        <div className="skeleton-line skeleton-line-short" />
        <div className="skeleton-line skeleton-line-price" />
      </div>
    </div>
  )
}
