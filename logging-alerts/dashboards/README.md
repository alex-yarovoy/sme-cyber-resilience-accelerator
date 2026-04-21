# Dashboards (Kibana)

Phase 0 does **not** ship version-locked Kibana **NDJSON** exports: saved-object formats drift across Elastic Stack minor releases and quickly go stale in a reference repo.

**Today:** after `docker compose up` (see kit [README](../README.md)), use **Discover** in Kibana:

1. Stack Management → Data Views → Create data view → index pattern `filebeat-*` (or `logs-*` depending on your ingest naming).
2. Open **Discover** and search for `service.name` or `message` fields from your sample logs.

**Planned:** curated dashboards and searches exported as NDJSON under this folder, pinned to a documented Elastic version — see [ROADMAP.md](../../ROADMAP.md).
