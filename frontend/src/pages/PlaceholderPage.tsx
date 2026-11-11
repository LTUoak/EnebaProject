import { Link } from 'react-router-dom'

/**
 * Stands in for pages that are not built yet, so every route already exists
 * and navigation can be tested. Each real page replaces one use of this.
 */
export function PlaceholderPage({ title, text = 'This page is coming soon.' }: { title: string; text?: string }) {
  return (
    <section className="container placeholder">
      <h1>{title}</h1>
      <p>{text}</p>
      <Link to="/" className="button button-ghost">
        Back to the homepage
      </Link>
    </section>
  )
}
