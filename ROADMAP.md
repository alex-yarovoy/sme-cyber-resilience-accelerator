# Roadmap

Releases follow [semantic versioning](https://semver.org/). Each kit release ships setup instructions, automated acceptance checks, known limitations, and an updated [control-to-evidence checklist](docs/CONTROL-MAPPING.md). Delivered work moves to the [changelog](CHANGELOG.md).

## v0.1.0 — shipped

| Area | Delivered |
|------|-----------|
| Identity and MFA | TOTP MFA with access token withheld until the second factor succeeds, risk-based step-up, login and MFA rate limiting, Argon2id hashing, JWT with `jti` denylist and single-use refresh rotation, audit log, `/api/health` and `/api/metrics`, WebAuthn ceremony with server-side challenge binding |
| Logging and Alerts | Filebeat → Elasticsearch → Kibana, `sme-redact` ingest pipeline (tokens, credentials, card numbers, emails), Prometheus with blackbox probe, alert rules for login and MFA failure bursts and unavailable endpoints, Alertmanager routing |
| Backup and Recovery Drills | PostgreSQL and MySQL backup and restore, AES-256 encryption with PBKDF2, verified restore drill with per-table row-count and checksum comparison, timed text and JSON drill report |
| Acceptance checks in CI | PHPUnit, ingest-pipeline redaction cases, `promtool` alert rule tests, end-to-end backup and restore drill, ShellCheck, Compose and Terraform validation, console tests and build |
| Deployment | Docker Compose per kit; cloud-neutral Terraform modules; Kustomize base and overlays for identity, backup CronJob template |
| Operator console | Assessment, controls, rollout, identity, audit, detection, recovery, architecture and sector playbook views with seeded sample tenants |

## v0.2 — account lifecycle and alert exercises

- **Identity:** account inventory export (privileged and staff accounts listed separately); joiner/mover/leaver procedure; periodic least-privilege review record; WebAuthn attestation and assertion signature verification.
- **Logging:** timed synthetic alert exercise that records delivery and acknowledgement timestamps; incident triage runbook with first-hour checklist and escalation path; redaction for structured fields beyond `message`.
- **Backup:** off-site copy to S3-compatible storage with object-lock retention; drill report fields for the agreed recovery time and data-loss objectives, with pass/fail against them.
- **Documentation:** per-kit operating runbooks with prerequisites and known limitations.

## v0.3 — rollout tooling

- Baseline assessment templates: dated asset and account inventory, risk register, baseline findings list.
- Scoped 90-day rollout plan: inventory and baseline, agreed controls, exercises and retest, handover.
- Result sheet template that records deployed version, deviations, exclusions and measured results.
- Operator console wired to live identity, logging and backup telemetry.

## v0.4 — delivery at scale

- Provider-specific Terraform modules (managed PostgreSQL, Redis, object storage, load balancing) composed per kit.
- Production Kubernetes overlays: external secret stores, resource limits, ingress with TLS, Prometheus scrape configuration.
- Training materials for MSPs and technical delivery partners; workshop labs built on the acceptance checks.

## Ongoing

- Quarterly review of configurations, pinned image versions and documentation.
- At least two substantive releases per year.
- Reproduction reports from independent operators feed fixes into the next release (see [docs/REPRODUCING.md](docs/REPRODUCING.md)).

## Terraform

`terraform/` defines cloud-neutral module boundaries and outputs (`network`, `postgres`, `object_storage`, `identity_mfa`) with an example environment validated in CI. Provider implementations, remote state and per-environment promotion arrive in v0.4.

## Kubernetes

`kubernetes/` provides a Kustomize base with dev and prod overlays for the identity kit, a CronJob for scheduled PostgreSQL backups, and guidance for running the logging stack in-cluster or against a managed Elastic service. Teams with an existing cluster can start from these manifests; v0.4 adds external secrets, ingress and scrape configuration.

## Requests

Open an issue with your target cloud, Kubernetes distribution, and constraints. Pull requests should stay focused, documented, and reproducible from a clean checkout.
