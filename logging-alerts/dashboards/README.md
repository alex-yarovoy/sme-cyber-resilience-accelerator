# Dashboards (Kibana)

Version-locked Kibana **NDJSON** exports are **planned**: saved-object formats drift across Elastic Stack minor releases, so exports are shipped only when pinned to a documented Elastic version (see [ROADMAP.md](../../ROADMAP.md)).

**Current workflow:** after `docker compose up` (see [README](../README.md)), use **Discover** in Kibana:

1. Stack Management → Data Views → Create data view → index pattern `filebeat-*` (or `logs-*` depending on your ingest naming).
2. Open **Discover** and search for `service.name` or `message` fields from your sample logs.

**Next:** curated dashboards and searches exported as NDJSON under this folder, aligned with the Elastic version in `docker-compose.yml`.
