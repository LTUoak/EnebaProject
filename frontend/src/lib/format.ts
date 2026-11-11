const euro = new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' })

/** 1999 -> "€19.99". Prices travel as whole cents and only become text here. */
export function formatPrice(cents: number): string {
  return euro.format(cents / 100)
}
