import { useEffect, useRef, useState, type FormEvent } from 'react'
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import { useCart } from '../lib/cart-context'
import { CartIcon, CloseIcon, SearchIcon, UserIcon } from './icons'
import './Header.css'

/**
 * The bar at the top of every page, following Eneba's layout: logo on the
 * left, icon buttons on the right. The search icon opens a search row.
 */
export function Header() {
  const location = useLocation()
  const [searchParams] = useSearchParams()
  const currentSearch = searchParams.get('search') ?? ''
  const cart = useCart()
  const [searchOpen, setSearchOpen] = useState(false)

  return (
    <header className="header">
      <div className="container header-inner">
        <Link to="/" className="header-logo" aria-label="Eneba home">
          eneba
        </Link>

        <nav className="header-actions" aria-label="Main">
          <button
            type="button"
            className="header-icon"
            aria-label={searchOpen ? 'Close search' : 'Search'}
            aria-expanded={searchOpen}
            onClick={() => setSearchOpen((open) => !open)}
          >
            {searchOpen ? <CloseIcon /> : <SearchIcon />}
          </button>
          <Link to="/cart" className="header-icon" aria-label={`Cart, ${cart.count} items`}>
            <CartIcon />
            {cart.count > 0 && <span className="header-badge">{cart.count}</span>}
          </Link>
          <Link to="/sign-in" className="header-icon" aria-label="Account">
            <UserIcon />
          </Link>
        </nav>
      </div>

      {searchOpen && (
        // The key restarts the row when the page or search text changes, which
        // also refills the box with the current search.
        <SearchRow
          key={location.pathname + currentSearch}
          initialText={currentSearch}
          onClose={() => setSearchOpen(false)}
        />
      )}
    </header>
  )
}

/** Typing here and pressing Enter opens the store page with ?search=... in the address. */
function SearchRow({ initialText, onClose }: { initialText: string; onClose: () => void }) {
  const navigate = useNavigate()
  const [text, setText] = useState(initialText)
  const input = useRef<HTMLInputElement>(null)

  useEffect(() => {
    input.current?.focus()
  }, [])

  function submitSearch(event: FormEvent) {
    event.preventDefault()
    const query = text.trim()
    navigate(query ? `/store?search=${encodeURIComponent(query)}` : '/store')
    onClose()
  }

  return (
    <div className="header-search-row">
      <form className="container header-search" role="search" onSubmit={submitSearch}>
        <label htmlFor="header-search-input" className="visually-hidden">
          Search games
        </label>
        <input
          id="header-search-input"
          ref={input}
          type="search"
          placeholder="Search for games, e.g. Elden Ring"
          value={text}
          onChange={(event) => setText(event.target.value)}
          onKeyDown={(event) => event.key === 'Escape' && onClose()}
          maxLength={100}
        />
        <button type="submit" className="button button-primary">
          Search
        </button>
      </form>
    </div>
  )
}
