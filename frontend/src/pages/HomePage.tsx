import { BannerBlock } from '../components/home/BannerBlock'
import { BestSellersBlock } from '../components/home/BestSellersBlock'
import { DealsBlock } from '../components/home/DealsBlock'

/**
 * The homepage is three independent blocks. Each one fetches from its own
 * backend endpoint and shows its own loading and error state.
 */
export function HomePage() {
  return (
    <div className="home">
      <BannerBlock />
      <BestSellersBlock />
      <DealsBlock />
    </div>
  )
}
