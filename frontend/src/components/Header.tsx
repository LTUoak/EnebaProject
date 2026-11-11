import { useState, type FormEvent } from 'react'
import { Link, NavLink, useNavigate, useSearchParams } from 'react-router-dom'
import { SITE_NAME } from '../config'
import './Header.css'

/** The bar at the top of every page: logo, search box and main links. */
export function Header() {
  const [searchParams] = useSearchParams()
  const currentSearch = searchParams.get('search') ?? ''

  return (
    <header className="header">
      <div className="container header-inner">
        <Link to="/" className="header-logo">
          {SITE_NAME}
        </Link>

        {/* The key restarts the box whenever the address's search text changes
            (back button, links), so it always shows the current search. */}
        <SearchBox key={currentSearch} initialText={currentSearch} />

        <nav className="header-nav" aria-label="Main">
          <NavLink to="/store">Store</NavLink>
          <NavLink to="/cart">Cart</NavLink>
          <NavLink to="/sign-in">Sign in</NavLink>
        </nav>
      </div>
    </header>
  )
}

/** Typing here and pressing Enter opens the store page with ?search=... in the address. */
function SearchBox({ initialText }: { initialText: string }) {
  const navigate = useNavigate()
  const [text, setText] = useState(initialText)

  function submitSearch(event: FormEvent) {
    event.preventDefault()
    const query = text.trim()
    navigate(query ? `/store?search=${encodeURIComponent(query)}` : '/store')
  }

  return (
    <form className="header-search" role="search" onSubmit={submitSearch}>
      <label htmlFor="header-search-input" className="visually-hidden">
        Search games
      </label>
      <input
        id="header-search-input"
        type="search"
        placeholder="Search games"
        value={text}
        onChange={(event) => setText(event.target.value)}
        maxLength={100}
      />
      <button type="submit" className="button button-primary">
        Search
      </button>
    </form>
  )
}
