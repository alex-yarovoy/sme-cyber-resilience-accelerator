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

Example rules live under [`alert-rules/`](alert-rules/): failed-login and MFA-failure rates (thresholds expressed per minute) and an availability rule on the blackbox HTTP probe. Thresholds are starting points; tune them to your traffic. Unit tests for every rule are in [`tests/auth.rules.test.yml`](tests/auth.rules.test.yml).

## Dashboards

Version-pinned **NDJSON** exports for dashboards are tracked on **[ROADMAP.md](../ROADMAP.md)**. Until those land, build views in **Discover** as above, or read [`dashboards/README.md`](dashboards/README.md) for the saved-object plan.

## Sensitive-field redaction

Filebeat sends every event through the `sme-redact` ingest pipeline ([`configs/ingest-pipeline.json`](configs/ingest-pipeline.json)); the one-shot `pipeline-setup` service loads it before Filebeat starts. The pipeline masks, inside `message`:

- bearer tokens → `Bearer [REDACTED]`
- `password=`, `secret=`, `token=`, `api_key=` style key-value pairs (plain or JSON) → `[REDACTED]`
- 13–19 digit card-like numbers, with or without spaces or dashes → `[PAN]`
- email addresses → `[EMAIL]`

Known limitations: only the `message` field is processed; other structured fields must be added as extra processors. The card-number pattern is deliberately broad and also masks other 13–19 digit numbers (for example millisecond epoch timestamps). Pattern-based redaction reduces exposure but does not replace keeping secrets out of logs at the source.

## Acceptance checks

| Check | What it proves | Run (from `logging-alerts/`) |
|-------|----------------|------------------------------|
| Alert rule unit tests | `HighLoginFailures`, `MFABurstFailures` and `EndpointDown` fire on synthetic series above threshold, stay silent below it, and respect their `for:` windows | `promtool test rules tests/auth.rules.test.yml` |
| Redaction check | Each case in [`tests/redaction-cases.json`](tests/redaction-cases.json) comes out of the pipeline exactly as expected, including values that must stay readable | `ES_URL=http://localhost:9200 tests/redaction_check.sh` |

Both checks run in CI on every push. Without local Prometheus tooling, use the container: `docker run --rm -v "$PWD":/w -w /w/tests --entrypoint promtool prom/prometheus:v2.53.0 test rules auth.rules.test.yml`.
