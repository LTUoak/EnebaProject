import { createContext, useContext } from 'react'
import type { User } from '../api/types'

export interface Auth {
  /** The signed-in user, or null. */
  user: User | null
  /** True until the first "who am I" check has finished after a page load. */
  loading: boolean
  signIn: (email: string, password: string) => Promise<void>
  signUp: (email: string, password: string, displayName: string) => Promise<void>
  signOut: () => Promise<void>
}

export const AuthContext = createContext<Auth | null>(null)

/** Gives any component access to the signed-in user and the sign-in actions. */
export function useAuth(): Auth {
  const auth = useContext(AuthContext)
  if (!auth) throw new Error('useAuth must be used inside <AuthProvider>')
  return auth
}
