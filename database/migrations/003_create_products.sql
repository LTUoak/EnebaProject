-- 003: Products. One row per thing you can buy a key for, e.g.
-- "Split Fiction (PC) Steam Key GLOBAL". Platform and region are part of the
-- product, because a Steam GLOBAL key and an Xbox EUROPE key are different goods.
-- There is no price here: prices belong to offers (see 004).

CREATE TABLE products (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    slug            TEXT        NOT NULL UNIQUE,       -- URL part, e.g. "split-fiction-pc-steam-key-global"
    title           TEXT        NOT NULL,
    description     TEXT        NOT NULL DEFAULT '',
    cover_image_url TEXT,
    platform        TEXT        NOT NULL,              -- where the key is activated: "Steam", "Xbox Live", "PSN", ...
    region          TEXT        NOT NULL,              -- where the key works: "GLOBAL", "EUROPE", ...
    release_date    DATE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Typo-tolerant search on the title (requires pg_trgm from 001).
CREATE INDEX products_title_trgm_idx ON products USING gin (title gin_trgm_ops);

-- Store filters.
CREATE INDEX products_platform_idx ON products (platform);
CREATE INDEX products_region_idx   ON products (region);
