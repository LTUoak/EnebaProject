import { useEffect, useState, type DependencyList } from 'react'

export type ApiState<T> =
  | { status: 'loading' }
  | { status: 'ok'; data: T }
  | { status: 'error'; message: string }

/**
 * Loads data from the backend for a component and tracks the three states
 * every request goes through: loading, ok, error.
 *
 *   const { state, reload } = useApi(() => api.deals(), [])
 *
 * `deps` works like useEffect's list: when a value in it changes, the data is
 * loaded again. `reload` retries after an error.
 */
export function useApi<T>(load: () => Promise<T>, deps: DependencyList) {
  const [state, setState] = useState<ApiState<T>>({ status: 'loading' })
  const [attempt, setAttempt] = useState(0)

  useEffect(() => {
    // If the component goes away (or deps change) before the answer arrives,
    // the late answer must be ignored.
    let cancelled = false
    load()
      .then((data) => {
        if (!cancelled) setState({ status: 'ok', data })
      })
      .catch((error: Error) => {
        if (!cancelled) setState({ status: 'error', message: error.message })
      })
    return () => {
      cancelled = true
    }
    // `load` is a new function on every render; the caller's deps decide when to reload.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [...deps, attempt])

  function reload() {
    setState({ status: 'loading' })
    setAttempt((n) => n + 1)
  }

  return { state, reload }
}
