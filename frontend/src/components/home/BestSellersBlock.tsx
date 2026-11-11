import { api } from '../../api/client'
import { ProductBlock } from './ProductBlock'

/** Homepage block: the products with the most copies sold. Data: GET /api/home/best-sellers */
export function BestSellersBlock() {
  return <ProductBlock title="Best sellers" load={api.bestSellers} moreLink="/store" />
}
