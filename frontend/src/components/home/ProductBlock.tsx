import { Link } from 'react-router-dom'
import type { ProductCard as ProductCardData } from '../../api/types'
import { useApi } from '../../lib/useApi'
import { ProductCard, ProductCardSkeleton } from '../ProductCard'
import './home.css'

interface ProductBlockProps {
  title: string
  /** The function that fetches this block's products from its own endpoint. */
  load: () => Promise<ProductCardData[]>
  /** Where "See all" leads. */
  moreLink: string
}

/**
 * A titled row of product cards that loads its own data. It handles its own
 * loading and error states, so a problem here never affects another block.
 */
export function ProductBlock({ title, load, moreLink }: ProductBlockProps) {
  const { state, reload } = useApi(load, [])

  return (
    <section className="home-block">
      <div className="home-block-header">
        <h2>{title}</h2>
        <Link to={moreLink} className="home-block-more">
          See all
        </Link>
      </div>

      {state.status === 'error' && (
        <div className="home-block-message" role="alert">
          <p>Could not load {title.toLowerCase()}: {state.message}</p>
          <button type="button" className="button button-ghost" onClick={reload}>
            Try again
          </button>
        </div>
      )}

      {state.status === 'ok' && state.data.length === 0 && (
        <p className="home-block-message">Nothing here yet.</p>
      )}

      {state.status !== 'error' && (
        <div className="product-grid" aria-busy={state.status === 'loading'}>
          {state.status === 'loading'
            ? Array.from({ length: 8 }, (_, i) => <ProductCardSkeleton key={i} />)
            : state.data.map((product) => <ProductCard key={product.id} product={product} />)}
        </div>
      )}
    </section>
  )
}
