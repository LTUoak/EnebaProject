import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api/client'
import { SITE_NAME } from '../config'

type Status = { state: 'loading' } | { state: 'ok'; products: number } | { state: 'error'; message: string }

/**
 * Temporary homepage. It asks the backend how many products the store has,
 * which proves the whole chain works: browser -> API -> database.
 * The banner, best sellers and deals blocks replace this next.
 */
export function HomePage() {
  const [status, setStatus] = useState<Status>({ state: 'loading' })

  useEffect(() => {
    let cancelled = false
    api
      .products({ per_page: 1 })
      .then((list) => {
        if (!cancelled) setStatus({ state: 'ok', products: list.total })
      })
      .catch((error: Error) => {
        if (!cancelled) setStatus({ state: 'error', message: error.message })
      })
    return () => {
      cancelled = true
    }
  }, [])

  return (
    <section className="placeholder">
      <h1>{SITE_NAME}</h1>
      <p>Game keys from several sellers, at the best price.</p>

      <p className="placeholder-status" role="status">
        {status.state === 'loading' && 'Connecting to the store… (the first load can take up to a minute)'}
        {status.state === 'ok' && `Connected: ${status.products} products in the store.`}
        {status.state === 'error' && `Could not load the store: ${status.message}`}
      </p>

      <Link to="/store" className="button button-primary">
        Browse the store
      </Link>
    </section>
  )
}
