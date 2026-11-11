import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../../api/client'
import { useApi } from '../../lib/useApi'
import { GamepadIcon, MonogramIcon, TagIcon } from '../icons'
import './home.css'

/** Short letters for each platform's badge. Unknown platforms use their first letter. */
const PLATFORM_LETTERS: Record<string, string> = {
  Steam: 'S',
  'Xbox Live': 'X',
  PSN: 'PS',
  'Nintendo eShop': 'N',
  'EA App': 'EA',
  GOG: 'G',
  Rockstar: 'R',
}

/**
 * Homepage block: the row of categories under the banner. Each one opens the
 * store already filtered. The platform list comes from the backend, so a new
 * platform in the database appears here by itself.
 * Data: GET /api/products/filters
 */
export function CategoriesBlock() {
  const { state } = useApi(api.productFilters, [])

  // Until the platforms arrive (or if they fail), the two fixed categories still work.
  const platforms = state.status === 'ok' ? state.data.platforms : []

  return (
    <nav className="categories container" aria-label="Categories">
      <Category to="/store" label="Games" icon={<GamepadIcon />} />
      <Category to="/store?on_sale=1&sort=discount" label="Deals" icon={<TagIcon />} />
      {platforms.map((platform) => (
        <Category
          key={platform.value}
          to={`/store?platform=${encodeURIComponent(platform.value)}`}
          label={platform.value}
          icon={<MonogramIcon letters={PLATFORM_LETTERS[platform.value] ?? platform.value.charAt(0)} />}
        />
      ))}
    </nav>
  )
}

function Category({ to, label, icon }: { to: string; label: string; icon: ReactNode }) {
  return (
    <Link to={to} className="category">
      {icon}
      <span>{label}</span>
    </Link>
  )
}
