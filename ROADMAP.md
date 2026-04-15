# Roadmap

This document lists **planned** work. The Accelerator **ships today** as Docker Compose-oriented deployments, scripts, and documentation so you can run and adapt it on common clouds. **Kubernetes packaging** and **Terraform modules** are **not** in the default tree yet; they are scheduled as explicit next releases.

## Near term (documentation and portability)

- Tighten cross-component conventions (naming, env var contracts, health check patterns).
- Expand per-component README quick starts for bring-your-own object storage and managed PostgreSQL.
- **Backup + DR:** `mysqldump` / restore scripts mirroring the Postgres reference path.
- **Logging + alerts:** version-pinned Kibana **NDJSON** exports (saved searches + dashboard) under `logging-alerts/dashboards/` for the Elastic Stack version pinned in `docker-compose.yml`.
- Optional sample CI jobs (lint, image build, compose smoke) without mandating a single vendor pipeline.

## Terraform

Goal: **repeatable infrastructure** for each component and shared dependencies (network boundaries, identity-aware credentials, durable storage), with **remote state** and **environment separation** (for example development and staging).

Planned direction:

1. **Foundational modules** — VPC or equivalent network baseline, private subnets for data planes, least-privilege IAM or cloud-provider roles, encryption defaults for object storage and databases.
2. **Per-component modules** — Parameterized stacks that provision what each part needs at minimum:
   - **Identity + MFA:** managed database, secrets store integration, optional Redis-compatible cache, load balancer or gateway in front of the application tier.
   - **Logging + alerts:** object storage for snapshots where applicable, managed search or Elastic Cloud-style endpoints if chosen, scrape targets and alert routing as code.
   - **Backup + DR:** versioned object storage buckets with lifecycle rules, optional KMS or customer-managed keys, IAM scoped to backup and restore roles only.
3. **Operations** — Remote backend for state locks, workspace or stack per environment, documented drift review and promotion flow.

Deliverables favor **small composable modules** over one monolithic stack, consistent with the **clear boundaries** principle in the main README.

## Kubernetes

Goal: **production deployment** of the same services using **declarative manifests** (Helm charts and/or Kustomize overlays), for teams that standardize on Kubernetes.

Planned direction:

1. **Baseline packaging** — One deployable unit per component (or per logical service group), ConfigMaps and Secrets from external secret stores where possible, resource requests and limits, readiness and liveness probes aligned with existing health endpoints or TCP checks.
2. **Ingress and TLS** — Documented ingress assumptions; integration with **cert-manager** or equivalent for automatic TLS where clusters support it.
3. **Hardening defaults** — Non-root containers where images allow, **NetworkPolicy** templates restricting east-west traffic to minimum required paths, **Pod Security** standards or equivalent namespace-level guardrails for supported Kubernetes versions.
4. **Observability hooks** — ServiceMonitor or vendor-neutral scrape annotations where Prometheus is used; log shipper sidecar or DaemonSet patterns only where they do not duplicate the logging component’s own design.

Kubernetes work will **not** force adoption of all three components; each chart or overlay stays **optional** and **independently usable**.

## Priorities

Open an issue with your target cloud, Kubernetes distribution, and constraints. Pull requests for Terraform or Kubernetes should stay **focused**, **documented**, and **reproducible** from a clean checkout.
