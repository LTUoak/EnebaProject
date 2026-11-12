-- 009: Failed sign-in attempts, used to slow down password guessing.
-- One row per failed attempt. The API counts recent rows for an email address
-- (and for a network address) and refuses further attempts once there are too
-- many. Successful sign-ins are not stored; they clear the email's rows.

CREATE TABLE login_attempts (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email        TEXT        NOT NULL,                -- lower-cased address that was tried (it may not belong to any account)
    ip           TEXT        NOT NULL,                -- network address the attempt came from
    attempted_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX login_attempts_email_idx ON login_attempts (email, attempted_at);
CREATE INDEX login_attempts_ip_idx    ON login_attempts (ip, attempted_at);
