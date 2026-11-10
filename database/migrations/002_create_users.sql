-- 002: Users. Buyers and sellers are both rows here; a seller is a user
-- with is_seller = true, so no separate sellers table is needed.

CREATE TABLE users (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email         TEXT        NOT NULL,
    password_hash TEXT        NOT NULL,               -- output of PHP password_hash(), never the password itself
    display_name  TEXT        NOT NULL,               -- shown as the seller name on offers
    is_seller     BOOLEAN     NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- One account per email, regardless of letter case.
CREATE UNIQUE INDEX users_email_unique_idx ON users (lower(email));
