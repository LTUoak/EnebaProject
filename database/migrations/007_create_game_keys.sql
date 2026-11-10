-- 007: Game keys. Each row is one digital key a seller put in stock for an offer.
-- Unsold key:  order_item_id IS NULL  -> counts as stock.
-- Sold key:    order_item_id points to the order line that bought it, and
--              the buyer can see key_code in their order.
-- An order item with quantity 2 simply has two keys pointing at it.

CREATE TABLE game_keys (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    offer_id      BIGINT      NOT NULL REFERENCES offers (id),
    key_code      TEXT        NOT NULL UNIQUE,         -- e.g. "ABCDE-FGHIJ-KLMNO" (fake keys in this project)
    order_item_id BIGINT      REFERENCES order_items (id),
    sold_at       TIMESTAMPTZ,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),

    -- sold_at is set exactly when the key is assigned to an order item.
    CHECK ((order_item_id IS NULL) = (sold_at IS NULL))
);

-- Counting stock and picking the next free key for an offer.
CREATE INDEX game_keys_available_idx ON game_keys (offer_id) WHERE order_item_id IS NULL;

-- Showing the keys that belong to an order.
CREATE INDEX game_keys_order_item_idx ON game_keys (order_item_id) WHERE order_item_id IS NOT NULL;
