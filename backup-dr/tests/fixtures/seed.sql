-- Deterministic fixture for backup-dr/tests/acceptance.sh.
-- Every value is derived from generate_series, so row counts and content
-- checksums are identical on every run and every host.

CREATE TABLE customers (
    id          integer PRIMARY KEY,
    email       text NOT NULL UNIQUE,
    full_name   text NOT NULL,
    created_at  timestamp NOT NULL
);

CREATE TABLE orders (
    id           integer PRIMARY KEY,
    customer_id  integer NOT NULL REFERENCES customers (id),
    amount       numeric(10, 2) NOT NULL,
    status       text NOT NULL,
    placed_at    timestamp NOT NULL
);

CREATE TABLE audit_events (
    id          bigserial PRIMARY KEY,
    order_id    integer NOT NULL REFERENCES orders (id),
    event_type  text NOT NULL,
    payload     jsonb NOT NULL
);

INSERT INTO customers (id, email, full_name, created_at)
SELECT i,
       'customer' || i || '@example.test',
       'Customer ' || lpad(i::text, 4, '0'),
       timestamp '2026-01-01 00:00:00' + i * interval '1 hour'
FROM generate_series(1, 200) AS i;

INSERT INTO orders (id, customer_id, amount, status, placed_at)
SELECT i,
       (i % 200) + 1,
       round(((i * 37) % 10000) / 7.0, 2),
       (ARRAY['new', 'paid', 'shipped', 'refunded'])[(i % 4) + 1],
       timestamp '2026-02-01 00:00:00' + i * interval '13 minutes'
FROM generate_series(1, 500) AS i;

INSERT INTO audit_events (order_id, event_type, payload)
SELECT (i % 500) + 1,
       CASE WHEN i % 3 = 0 THEN 'order.updated' ELSE 'order.created' END,
       jsonb_build_object('seq', i, 'digest', md5(i::text))
FROM generate_series(1, 300) AS i;
