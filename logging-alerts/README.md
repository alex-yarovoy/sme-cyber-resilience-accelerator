# Logging + Alerts System

Vendor-agnostic centralized logging and alerting stack using Filebeat -> Elasticsearch -> Kibana plus Prometheus + Alertmanager for metrics-based alerts.

## Components
- Filebeat: log shipping from apps/containers
- Elasticsearch: log storage and indexing
- Kibana: dashboards and search
- Prometheus: metrics scraping
- Alertmanager: alert routing and escalation

## Security (P0 / lab only)

Elasticsearch runs with **`xpack.security.enabled=false`** in `docker-compose.yml` so the stack starts quickly on a laptop. **Do not expose this compose file to the internet** or treat it as production. For any shared or hosted environment, enable Elasticsearch/Kibana security (TLS, users, roles) or keep the stack on an isolated lab network.

## Quick start
Use `docker-compose.yml` to run the stack locally. Place your app logs into `./configs/sample-app.log` or mount real app logs.

## Alerting
- Error rate > 5% over 5m
- High latency p95 > 1s
- Service down (no heartbeats)

Dashboards JSON located in `dashboards/`.


