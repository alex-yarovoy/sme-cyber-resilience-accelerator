# Dashboards (Kibana)

## Shipped

- **`filebeat-8.12.2.ndjson`** — index pattern `filebeat-*` plus saved search **Application logs (filebeat-*)**, pinned to Elastic **8.12.2** (see `docker-compose.yml`).

## Import (Elastic 8.12.x)

1. Start the stack from `logging-alerts/` (`docker compose up -d`).
2. Open Kibana → **Stack Management** → **Saved Objects** → **Import**.
3. Select `filebeat-8.12.2.ndjson`.
4. Resolve conflicts if you already created a data view with the same title.
5. Open **Discover** or the imported saved search once Filebeat indices exist.

## Planned

- Full dashboard panels (error rate, service breakdown) exported as additional NDJSON objects for the same Elastic version pin.
