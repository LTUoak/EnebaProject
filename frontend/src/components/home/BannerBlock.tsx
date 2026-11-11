import { Link } from 'react-router-dom'
import { api } from '../../api/client'
import { useApi } from '../../lib/useApi'
import './home.css'

/**
 * Homepage block: the promo strip across the full width of the page, as on
 * Eneba. The first banner is the large advert; the others are narrow tiles
 * beside it. They advertise deals in general, and each links to a filtered
 * store page. Data: GET /api/home/banners
 */
export function BannerBlock() {
  const { state, reload } = useApi(api.banners, [])

  if (state.status === 'loading') {
    return <div className="promo promo-skeleton" aria-hidden="true" />
  }
  if (state.status === 'error') {
    return (
      <div className="promo promo-message" role="alert">
        <p>Could not load the offers: {state.message}</p>
        <button type="button" className="button button-ghost" onClick={reload}>
          Try again
        </button>
      </div>
    )
  }
  if (state.data.length === 0) {
    return null
  }

  const [main, ...tiles] = state.data

  return (
    <section className="promo" aria-label="Current deals">
      <Link to={main.link_url} className="promo-main">
        <img src={main.image_url} alt="" />
        <div className="promo-main-text">
          <h2>{main.title}</h2>
          {main.subtitle && <p>{main.subtitle}</p>}
          <span className="promo-cta">Shop now</span>
        </div>
      </Link>

      {tiles.map((tile) => (
        <Link key={tile.id} to={tile.link_url} className="promo-tile">
          <img src={tile.image_url} alt="" />
          <div className="promo-tile-text">
            <h3>{tile.title}</h3>
            {tile.subtitle && <p>{tile.subtitle}</p>}
          </div>
        </Link>
      ))}
    </section>
  )
}
