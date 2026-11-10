-- 005: Orders. One row per checkout by a buyer.
-- Payment is simulated: the order starts as 'pending' and becomes 'paid'
-- (keys are then assigned) or 'failed'.

CREATE TABLE orders (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id     BIGINT      NOT NULL REFERENCES users (id),
    status      TEXT        NOT NULL DEFAULT 'pending'
                CHECK (status IN ('pending', 'paid', 'failed')),
    total_cents INTEGER     NOT NULL CHECK (total_cents >= 0),  -- sum of the order's items, fixed at checkout
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    paid_at     TIMESTAMPTZ,

    -- paid_at is set exactly when the order is paid.
    CHECK ((status = 'paid') = (paid_at IS NOT NULL))
);

-- Order history page: a user's orders, newest first.
CREATE INDEX orders_user_created_idx ON orders (user_id, created_at DESC);
