import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../../api/client'
import type { Banner } from '../../api/types'
import { useApi } from '../../lib/useApi'
import './home.css'

const ROTATE_EVERY_MS = 6000

/** Homepage block: the large promo banners, one at a time. Data: GET /api/home/banners */
export function BannerBlock() {
  const { state, reload } = useApi(api.banners, [])

  if (state.status === 'loading') {
    return <div className="banner banner-skeleton" aria-hidden="true" />
  }
  if (state.status === 'error') {
    return (
      <div className="banner home-block-message" role="alert">
        <p>Could not load the banners: {state.message}</p>
        <button type="button" className="button button-ghost" onClick={reload}>
          Try again
        </button>
      </div>
    )
  }
  if (state.data.length === 0) {
    return null
  }
  return <BannerCarousel banners={state.data} />
}

function BannerCarousel({ banners }: { banners: Banner[] }) {
  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(false)
  const banner = banners[index]

  // Move to the next banner every few seconds, unless the visitor is pointing
  // at it or has asked their device for reduced motion.
  useEffect(() => {
    if (paused || banners.length < 2) return
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
    const timer = window.setTimeout(() => setIndex((i) => (i + 1) % banners.length), ROTATE_EVERY_MS)
    return () => window.clearTimeout(timer)
  }, [index, paused, banners.length])

  return (
    <section
      className="banner"
      aria-roledescription="carousel"
      aria-label="Promotions"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onFocus={() => setPaused(true)}
      onBlur={() => setPaused(false)}
    >
      <img className="banner-image" src={banner.image_url} alt="" />
      <div className="banner-content">
        <h2>{banner.title}</h2>
        {banner.subtitle && <p>{banner.subtitle}</p>}
        <Link to={banner.link_url} className="button button-primary">
          Shop now
        </Link>
      </div>

      {banners.length > 1 && (
        <div className="banner-dots">
          {banners.map((item, i) => (
            <button
              key={item.id}
              type="button"
              className={i === index ? 'banner-dot banner-dot-active' : 'banner-dot'}
              aria-label={`Show banner ${i + 1} of ${banners.length}: ${item.title}`}
              aria-current={i === index}
              onClick={() => setIndex(i)}
            />
          ))}
        </div>
      )}
    </section>
  )
}
