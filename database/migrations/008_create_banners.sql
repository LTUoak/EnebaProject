-- 008: Banners. Content for the homepage banner block (the large promo
-- images at the top). Stored in the database so the block gets its content
-- from the backend like every other homepage block, instead of being
-- hard-coded in the frontend.

CREATE TABLE banners (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    title      TEXT        NOT NULL,
    subtitle   TEXT        NOT NULL DEFAULT '',
    image_url  TEXT        NOT NULL,
    link_url   TEXT        NOT NULL,                  -- where a click leads, e.g. "/product/split-fiction-pc-steam-key-global" or "/store?platform=Steam"
    sort_order INTEGER     NOT NULL DEFAULT 0,        -- lower numbers are shown first
    is_active  BOOLEAN     NOT NULL DEFAULT TRUE,     -- switch a banner off without deleting it
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
