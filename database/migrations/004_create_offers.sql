-- 004: Offers. An offer is "seller X sells product Y for price Z".
-- Several sellers can offer the same product at different prices, which is
-- why the price lives here and not on the product.
-- All prices are in euro cents (1999 = 19.99 EUR) to avoid rounding errors.
-- Stock is not stored: it is the number of unsold game_keys for the offer.

CREATE TABLE offers (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    product_id  BIGINT      NOT NULL REFERENCES products (id),
    seller_id   BIGINT      NOT NULL REFERENCES users (id),
    price_cents INTEGER     NOT NULL CHECK (price_cents > 0),
    old_price_cents INTEGER,                           -- price before a discount; NULL = the offer is not on sale
    is_active   BOOLEAN     NOT NULL DEFAULT TRUE,     -- hide an offer without deleting it (orders still point to it)
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),

    -- A seller has at most one offer per product; they change its price instead of adding another.
    UNIQUE (product_id, seller_id),

    -- An old price only makes sense if it is higher than the current one.
    CHECK (old_price_cents IS NULL OR old_price_cents > price_cents)
);

-- Homepage "deals" block: only the offers that are on sale.
CREATE INDEX offers_deals_idx ON offers (product_id) WHERE old_price_cents IS NOT NULL;

-- Finding a product's offers cheapest-first (product page, "from X EUR" on cards).
CREATE INDEX offers_product_price_idx ON offers (product_id, price_cents);
CREATE INDEX offers_seller_idx        ON offers (seller_id);
