# Logging + Alerts System

Vendor-agnostic centralized logging and alerting stack using Filebeat → Elasticsearch → Kibana plus Prometheus + Alertmanager for metrics-based alerts.

## Components

- **Filebeat:** log shipping from files under `./configs/logs/`
- **Elasticsearch:** log storage and indexing
- **Kibana:** dashboards and search — minimal NDJSON under [`dashboards/`](dashboards/); expand per [ROADMAP.md](../ROADMAP.md)
- **Prometheus:** metrics scraping
- **Alertmanager:** alert routing and escalation

## Security (lab vs production)

Elasticsearch runs with **`xpack.security.enabled=false`** in `docker-compose.yml` so the stack starts quickly on a laptop. **Do not expose this compose file to the internet** or treat it as production.

### Enabling Elasticsearch security (production-oriented steps)

1. Set `xpack.security.enabled=true` and configure `ELASTIC_PASSWORD` (or bootstrap passwords) in `docker-compose.yml`.
2. Enable TLS for HTTP and transport (Elastic documentation for your pinned version: **8.12.2**).
3. Create Kibana system user and set `ELASTICSEARCH_USERNAME` / `ELASTICSEARCH_PASSWORD` on the Kibana service.
4. Update Filebeat output to use HTTPS and credentials (`configs/filebeat.yml`).
5. Restrict network access (firewall, private subnet, or VPN) so 9200/5601 are not public.

These steps are **your** operational responsibility; the repo ships an offline-friendly lab profile by default.

## Quick start (happy path)

From the `logging-alerts/` directory:

1. **Log pickup directory** — the repo ships `configs/logs/sample-app.log` with one JSON line. To add more lines locally:

   ```bash
   mkdir -p configs/logs
   echo '{"@timestamp":"2026-01-15T12:05:00Z","service":{"name":"sample-app"},"level":"error","message":"synthetic error for alerting lab"}' >> configs/logs/sample-app.log
   ```

2. **Start the stack**

   ```bash
   docker compose up -d
   ```

3. **Wait for Elasticsearch** (first boot can take 30–60s), then verify:

   ```bash
   curl -sS http://localhost:9200/_cluster/health?pretty
   ```

4. **Open Kibana** at [http://localhost:5601](http://localhost:5601) → **Discover** → create a **data view** on `filebeat-*` once indices appear.

5. **Prometheus** UI: [http://localhost:9090](http://localhost:9090) — targets should show `prometheus` itself per [`configs/prometheus.yml`](configs/prometheus.yml).

6. **Alertmanager** UI: [http://localhost:9093](http://localhost:9093)

7. **Shut down**

   ```bash
   docker compose down
   ```

## Alerting (examples in repo)

Example rules live under [`alert-rules/`](alert-rules/) (auth-focused samples). Tune thresholds for your environment; lab defaults are not production-ready.

## Dashboards

Version-pinned **NDJSON** exports for dashboards are tracked on **[ROADMAP.md](../ROADMAP.md)**. Until those land, build views in **Discover** as above, or read [`dashboards/README.md`](dashboards/README.md) for the saved-object plan.
