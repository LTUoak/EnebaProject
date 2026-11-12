import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../lib/auth-context'

/**
 * Wraps a page that needs sign-in (checkout, order history). Visitors who are
 * not signed in are sent to the sign-in page, which brings them back here
 * afterwards. This is for convenience only: the backend refuses these
 * requests without a session regardless of what the frontend does.
 */
export function RequireAuth({ children }: { children: ReactNode }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) {
    return <div className="container page-content" aria-busy="true" />
  }
  if (!user) {
    const next = encodeURIComponent(location.pathname + location.search)
    return <Navigate to={`/sign-in?next=${next}`} replace />
  }
  return children
}
