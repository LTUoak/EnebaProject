-- 001: Enable trigram matching, used for typo-tolerant product search.
-- pg_trgm compares strings by their 3-letter fragments, so "cyberpnuk"
-- still matches "Cyberpunk". It ships with PostgreSQL and is available on Neon.

CREATE EXTENSION IF NOT EXISTS pg_trgm;
