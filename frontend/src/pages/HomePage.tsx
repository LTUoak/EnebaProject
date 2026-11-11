import { BannerBlock } from '../components/home/BannerBlock'
import { BestSellersBlock } from '../components/home/BestSellersBlock'
import { CategoriesBlock } from '../components/home/CategoriesBlock'
import { DealsBlock } from '../components/home/DealsBlock'

/**
 * The homepage is made of independent blocks. Each one fetches from its own
 * backend endpoint and shows its own loading and error state.
 */
export function HomePage() {
  return (
    <div className="home">
      <BannerBlock />
      <CategoriesBlock />
      <div className="container">
        <BestSellersBlock />
      </div>
      <div className="container">
        <DealsBlock />
      </div>
    </div>
  )
}
