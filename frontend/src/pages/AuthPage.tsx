import { useState, type FormEvent, type ReactNode } from 'react'
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom'
import { ApiError } from '../api/client'
import { useAuth } from '../lib/auth-context'
import './account.css'

/** Only follow "next" addresses inside this site, so a crafted link cannot send people elsewhere. */
function safeNext(next: string | null): string {
  return next && next.startsWith('/') && !next.startsWith('//') ? next : '/'
}

/** The sign-in and sign-up forms. They differ only in one extra field and which action runs. */
export function AuthPage({ mode }: { mode: 'sign-in' | 'sign-up' }) {
  const auth = useAuth()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const next = safeNext(params.get('next'))
  const nextQuery = next === '/' ? '' : `?next=${encodeURIComponent(next)}`

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [name, setName] = useState('')
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [busy, setBusy] = useState(false)

  if (auth.user) {
    return <Navigate to={next} replace />
  }

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError('')
    setFieldErrors({})
    try {
      if (mode === 'sign-in') await auth.signIn(email, password)
      else await auth.signUp(email, password, name)
      navigate(next, { replace: true })
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : 'Something went wrong.')
      setFieldErrors(caught instanceof ApiError ? caught.fields : {})
      setBusy(false)
    }
  }

  const isSignUp = mode === 'sign-up'

  return (
    <div className="container page-content">
      <form className="auth" onSubmit={submit} noValidate>
        <h1>{isSignUp ? 'Create an account' : 'Sign in'}</h1>

        {error && (
          <p className="form-error" role="alert">
            {error}
          </p>
        )}

        {isSignUp && (
          <Field label="Name" error={fieldErrors.display_name}>
            <input value={name} onChange={(e) => setName(e.target.value)} autoComplete="name" required />
          </Field>
        )}
        <Field label="Email" error={fieldErrors.email}>
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required />
        </Field>
        <Field label="Password" error={fieldErrors.password} hint={isSignUp ? 'At least 8 characters.' : undefined}>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete={isSignUp ? 'new-password' : 'current-password'}
            required
          />
        </Field>

        <button type="submit" className="button button-primary auth-submit" disabled={busy}>
          {busy ? 'Please wait…' : isSignUp ? 'Create account' : 'Sign in'}
        </button>

        <p className="auth-switch">
          {isSignUp ? (
            <>
              Already have an account? <Link to={`/sign-in${nextQuery}`}>Sign in</Link>
            </>
          ) : (
            <>
              New here? <Link to={`/sign-up${nextQuery}`}>Create an account</Link>
            </>
          )}
        </p>
      </form>
    </div>
  )
}

function Field(props: { label: string; error?: string; hint?: string; children: ReactNode }) {
  return (
    <label className="field">
      <span>{props.label}</span>
      {props.children}
      {props.hint && !props.error && <small>{props.hint}</small>}
      {props.error && <small className="field-error">{props.error}</small>}
    </label>
  )
}
