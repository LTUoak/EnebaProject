import { api } from '../../api/client'
import { ProductBlock } from './ProductBlock'

/** Homepage block: the biggest discounts. Data: GET /api/home/deals */
export function DealsBlock() {
  return <ProductBlock title="Top deals" load={api.deals} moreLink="/store?on_sale=1&sort=discount" />
}
