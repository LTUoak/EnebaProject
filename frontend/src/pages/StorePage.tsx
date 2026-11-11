import { useState, type FormEvent } from 'react'
import { useSearchParams } from 'react-router-dom'
import { api } from '../api/client'
import type { FilterOption, ProductQuery, ProductSort } from '../api/types'
import { ProductCard, ProductCardSkeleton } from '../components/ProductCard'
import { useApi } from '../lib/useApi'
import './store.css'

const SORT_LABELS: Record<ProductSort, string> = {
  relevance: 'Best match',
  newest: 'Newest',
  price_asc: 'Price: low to high',
  price_desc: 'Price: high to low',
  discount: 'Biggest discount',
  title: 'Name: A to Z',
}

/** "Steam,GOG" -> ['Steam', 'GOG'] */
const toList = (value: string | null) => (value ? value.split(',').filter(Boolean) : [])
/** "1999" -> 1999; anything that is not a whole number -> undefined */
const toNumber = (value: string | null) => (value && /^\d+$/.test(value) ? Number(value) : undefined)
/** cents from the address -> text for a euro input: "1000" -> "10" */
const toEuros = (cents: string | null) => (toNumber(cents) === undefined ? '' : String(Number(cents) / 100))

/**
 * The store: every product, with search, filters, sorting and paging.
 *
 * The page address is the single source of truth: every choice is written to
 * the query string (?platform=Steam&sort=price_asc) and the list is loaded
 * from it. So a filtered view can be reloaded, bookmarked, shared, and the
 * back button undoes the last choice.
 */
export function StorePage() {
  const [params, setParams] = useSearchParams()

  const search = params.get('search') ?? ''
  const platforms = toList(params.get('platform'))
  const regions = toList(params.get('region'))
  const onSale = params.get('on_sale') === '1'
  const page = toNumber(params.get('page')) ?? 1

  const query: ProductQuery = {
    search,
    platform: platforms,
    region: regions,
    min_price: toNumber(params.get('min_price')),
    max_price: toNumber(params.get('max_price')),
    on_sale: onSale,
    sort: (params.get('sort') as ProductSort | null) ?? undefined,
    page,
  }

  const products = useApi(() => api.products(query), [params.toString()])
  const filters = useApi(api.productFilters, [])

  /** Changes some query-string values (null removes one). Any change except paging returns to page 1. */
  function update(changes: Record<string, string | null>) {
    const next = new URLSearchParams(params)
    if (!('page' in changes)) next.delete('page')
    for (const [key, value] of Object.entries(changes)) {
      if (value === null || value === '') next.delete(key)
      else next.set(key, value)
    }
    setParams(next)
  }

  function toggle(key: 'platform' | 'region', selected: string[], value: string) {
    const next = selected.includes(value) ? selected.filter((item) => item !== value) : [...selected, value]
    update({ [key]: next.join(',') })
  }

  const hasFilters =
    platforms.length > 0 || regions.length > 0 || onSale || params.has('min_price') || params.has('max_price')
  const data = products.state.status === 'ok' ? products.state.data : null

  return (
    <div className="container page-content">
      <h1 className="page-title">{search ? `Results for “${search}”` : 'Store'}</h1>

      <div className="store">
        <aside className="store-filters" aria-label="Filters">
          {filters.state.status === 'ok' && (
            <>
              <FilterGroup
                title="Platform"
                options={filters.state.data.platforms}
                selected={platforms}
                onToggle={(value) => toggle('platform', platforms, value)}
              />
              <FilterGroup
                title="Region"
                options={filters.state.data.regions}
                selected={regions}
                onToggle={(value) => toggle('region', regions, value)}
              />
            </>
          )}

          {/* The key refills the boxes when the address changes (back button, "Clear filters"). */}
          <PriceFilter
            key={`${params.get('min_price')}-${params.get('max_price')}`}
            min={toEuros(params.get('min_price'))}
            max={toEuros(params.get('max_price'))}
            onApply={(min, max) => update({ min_price: min, max_price: max })}
          />

          <label className="store-check">
            <input type="checkbox" checked={onSale} onChange={() => update({ on_sale: onSale ? null : '1' })} />
            <span>On sale only</span>
          </label>

          {hasFilters && (
            <button
              type="button"
              className="button button-ghost"
              onClick={() =>
                update({ platform: null, region: null, min_price: null, max_price: null, on_sale: null })
              }
            >
              Clear filters
            </button>
          )}
        </aside>

        <section className="store-results" aria-live="polite">
          <div className="store-toolbar">
            <p>{data ? `${data.total} ${data.total === 1 ? 'product' : 'products'}` : ' '}</p>
            <label className="store-sort">
              <span>Sort by</span>
              <select
                value={data?.sort ?? query.sort ?? (search ? 'relevance' : 'newest')}
                onChange={(event) => update({ sort: event.target.value })}
              >
                {(Object.keys(SORT_LABELS) as ProductSort[])
                  .filter((sort) => sort !== 'relevance' || search !== '')
                  .map((sort) => (
                    <option key={sort} value={sort}>
                      {SORT_LABELS[sort]}
                    </option>
                  ))}
              </select>
            </label>
          </div>

          {products.state.status === 'error' && (
            <div className="notice" role="alert">
              <p>Could not load the products: {products.state.message}</p>
              <button type="button" className="button button-ghost" onClick={products.reload}>
                Try again
              </button>
            </div>
          )}

          {products.state.status === 'loading' && (
            <div className="store-grid" aria-busy="true">
              {Array.from({ length: 8 }, (_, i) => (
                <ProductCardSkeleton key={i} />
              ))}
            </div>
          )}

          {data && data.items.length === 0 && (
            <div className="notice">
              <h2>No products found</h2>
              <p>
                {search
                  ? 'Check the spelling or try a shorter search. Small typos are fine, abbreviations are not.'
                  : 'Try removing some filters.'}
              </p>
            </div>
          )}

          {data && data.items.length > 0 && (
            <>
              <div className="store-grid">
                {data.items.map((product) => (
                  <ProductCard key={product.id} product={product} />
                ))}
              </div>

              {data.total_pages > 1 && (
                <nav className="store-pages" aria-label="Pages">
                  {Array.from({ length: data.total_pages }, (_, i) => i + 1).map((number) => (
                    <button
                      key={number}
                      type="button"
                      className={number === data.page ? 'store-page store-page-current' : 'store-page'}
                      aria-current={number === data.page ? 'page' : undefined}
                      onClick={() => {
                        update({ page: number === 1 ? null : String(number) })
                        window.scrollTo({ top: 0 })
                      }}
                    >
                      {number}
                    </button>
                  ))}
                </nav>
              )}
            </>
          )}
        </section>
      </div>
    </div>
  )
}

function FilterGroup(props: {
  title: string
  options: FilterOption[]
  selected: string[]
  onToggle: (value: string) => void
}) {
  return (
    <fieldset className="store-group">
      <legend>{props.title}</legend>
      {props.options.map((option) => (
        <label key={option.value} className="store-check">
          <input
            type="checkbox"
            checked={props.selected.includes(option.value)}
            onChange={() => props.onToggle(option.value)}
          />
          <span>{option.value}</span>
          <span className="store-count">{option.count}</span>
        </label>
      ))}
    </fieldset>
  )
}

/** Two euro boxes and an Apply button. Sends cents (or null for an empty box) to onApply. */
function PriceFilter(props: { min: string; max: string; onApply: (min: string | null, max: string | null) => void }) {
  const [min, setMin] = useState(props.min)
  const [max, setMax] = useState(props.max)

  const toCents = (euros: string) => {
    const value = Number(euros.replace(',', '.'))
    return euros.trim() === '' || !Number.isFinite(value) || value < 0 ? null : String(Math.round(value * 100))
  }

  function apply(event: FormEvent) {
    event.preventDefault()
    props.onApply(toCents(min), toCents(max))
  }

  return (
    <form className="store-group" onSubmit={apply}>
      <p className="store-group-title">Price (€)</p>
      <div className="store-price">
        <input
          inputMode="decimal"
          placeholder="Min"
          aria-label="Lowest price in euros"
          value={min}
          onChange={(event) => setMin(event.target.value)}
        />
        <span>–</span>
        <input
          inputMode="decimal"
          placeholder="Max"
          aria-label="Highest price in euros"
          value={max}
          onChange={(event) => setMax(event.target.value)}
        />
        <button type="submit" className="button button-ghost">
          Apply
        </button>
      </div>
    </form>
  )
}
