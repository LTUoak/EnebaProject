-- 006: Order items. One row per offer bought within an order.
-- Needed because one order can contain several different offers, and because
-- the price paid must be frozen: the seller may change the offer price later,
-- but order history has to keep showing what the buyer actually paid.

CREATE TABLE order_items (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id         BIGINT  NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    offer_id         BIGINT  NOT NULL REFERENCES offers (id),
    quantity         INTEGER NOT NULL CHECK (quantity > 0),
    unit_price_cents INTEGER NOT NULL CHECK (unit_price_cents > 0),  -- offer price copied at checkout

    -- The same offer appears once per order; buying two copies means quantity = 2.
    UNIQUE (order_id, offer_id)
);

CREATE INDEX order_items_offer_idx ON order_items (offer_id);
