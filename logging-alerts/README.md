# Logging + Alerts System

Vendor-agnostic centralized logging and alerting stack using Filebeat → Elasticsearch → Kibana plus Prometheus + Alertmanager for metrics-based alerts.

## Components

- **Filebeat:** log shipping from files under `./configs/logs/`
- **Elasticsearch:** log storage and indexing
- **Kibana:** dashboards and search (use Discover in Phase 0; see below)
- **Prometheus:** metrics scraping
- **Alertmanager:** alert routing and escalation

## Security (P0 / lab only)

Elasticsearch runs with **`xpack.security.enabled=false`** in `docker-compose.yml` so the stack starts quickly on a laptop. **Do not expose this compose file to the internet** or treat it as production. For any shared or hosted environment, enable Elasticsearch/Kibana security (TLS, users, roles) or keep the stack on an isolated lab network.

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

There are **no committed NDJSON exports** in Phase 0. Use Discover as above, or read [`dashboards/README.md`](dashboards/README.md) for the roadmap for saved objects.
