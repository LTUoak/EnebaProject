import { useEffect, useMemo, useState, type ReactNode } from 'react'
import { api } from '../api/client'
import type { User } from '../api/types'
import { AuthContext, type Auth } from './auth-context'

/**
 * Knows who is signed in. The session itself is an HttpOnly cookie that this
 * code cannot read, so on page load it asks the backend "who am I?" and keeps
 * the answer. Only the user's name and email are held here, never a token.
 */
export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    api
      .me()
      .then((me) => {
        if (!cancelled) setUser(me)
      })
      .catch(() => {
        // The backend could not be reached: carry on as signed out.
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [])

  const auth = useMemo<Auth>(
    () => ({
      user,
      loading,
      signIn: async (email, password) => setUser((await api.login({ email, password })).user),
      signUp: async (email, password, displayName) =>
        setUser((await api.register({ email, password, display_name: displayName })).user),
      signOut: async () => {
        await api.logout()
        setUser(null)
      },
    }),
    [user, loading],
  )

  return <AuthContext.Provider value={auth}>{children}</AuthContext.Provider>
}
