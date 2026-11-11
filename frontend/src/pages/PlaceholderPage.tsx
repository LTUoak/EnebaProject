import { Link, useParams, useSearchParams } from 'react-router-dom'

/**
 * Stands in for pages that are not built yet, so every route already exists
 * and navigation can be tested. Each real page replaces one use of this.
 */
export function PlaceholderPage({ title }: { title: string }) {
  const { slug } = useParams()
  const [searchParams] = useSearchParams()
  const search = searchParams.get('search')

  return (
    <section className="placeholder">
      <h1>{title}</h1>
      <p>This page is coming soon.</p>
      {slug && <p>Product: {slug}</p>}
      {search && <p>Search: “{search}”</p>}
      <Link to="/" className="button button-ghost">
        Back to the homepage
      </Link>
    </section>
  )
}
