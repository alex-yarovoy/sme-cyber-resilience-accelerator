# SME Cyber Resilience Accelerator

[![CI](https://github.com/alex-yarovoy/sme-cyber-resilience-accelerator/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/alex-yarovoy/sme-cyber-resilience-accelerator/actions/workflows/ci.yml)
[![License: Apache-2.0](https://img.shields.io/badge/license-Apache--2.0-blue.svg)](LICENSE)

Three independently adoptable reference kits that help small and mid-sized organizations — and the managed service providers (MSPs) and engineering teams that support them — put **strong authentication**, **central logging with alerting**, and **tested recovery** in place on common web-application stacks. Typical environments are e-commerce storefronts, booking and property systems, and other customer-facing applications built on PHP, PostgreSQL or MySQL, Redis, and containers.

Each kit ships runnable configuration, **acceptance checks that run in CI on every push**, and an operating guide. The kits are designed to be adopted one at a time on existing systems.

## Kits

| Kit | Path | Stack | Acceptance checks in CI |
|-----|------|-------|-------------------------|
| **Identity and MFA** | [`identity-mfa/`](identity-mfa/) | Symfony 6.4, Vue 3, PostgreSQL, Redis | PHPUnit suite: login, MFA gating, refresh rotation, logout revocation, WebAuthn challenge binding, metrics |
| **Logging and Alerts** | [`logging-alerts/`](logging-alerts/) | Filebeat, Elasticsearch, Kibana, Prometheus, Alertmanager | Sensitive-field redaction cases through the ingest pipeline; unit tests for every alert rule |
| **Backup and Recovery Drills** | [`backup-dr/`](backup-dr/) | Shell, PostgreSQL / MySQL tooling, OpenSSL | Backup → encrypt → decrypt → restore into a clean database → per-table row-count and checksum verification, with a timed drill report |

The three kits share conventions (environment variables, log fields, health and metrics endpoints — see the [development handbook](docs/DEVELOPMENT-HANDBOOK.md#6-cross-component-consistency-conventions)) but no runtime: each deploys and upgrades on its own.

## How the kits fit together

1. **Identity and MFA** decides who gets in: TOTP and WebAuthn second factors, risk-based step-up, brute-force throttling, short-lived tokens with server-side revocation, and an audit trail of authentication events.
2. **Logging and Alerts** makes failures visible: central log collection with redaction of tokens, credentials, card numbers and emails before indexing; alerts on login and MFA failure bursts and on unavailable endpoints, routed through Alertmanager.
3. **Backup and Recovery Drills** proves recovery works: encrypted database backups and a drill that restores into a clean database, verifies the data, and records how long recovery took and how old the backup was.

Together they cover **prevent → detect → recover** for the accounts, events and data a small organization depends on.

## Control-to-evidence checklist

[`docs/CONTROL-MAPPING.md`](docs/CONTROL-MAPPING.md) lists every control with the NIST Cybersecurity Framework (CSF) 2.0 outcome it supports, the code that implements it, the evidence it produces, and how to verify it. Use it to agree scope with a system owner before rollout and to confirm afterwards that each control operates.

## Release status (v0.1.0)

| Area | Status |
|------|--------|
| Identity and MFA | TOTP MFA, risk-based step-up, rate limiting, JWT with refresh rotation and revocation, audit log, health and metrics endpoints. WebAuthn ships with server-side challenge binding; attestation and assertion signature verification are next on the [roadmap](ROADMAP.md). |
| Logging and Alerts | Collection, redaction pipeline, authentication and availability alert rules with unit tests. Alert receivers are configured per environment. |
| Backup and Recovery Drills | PostgreSQL and MySQL backup and restore, encryption, verified restore drill with machine-readable report. Off-site immutable copies are on the roadmap. |
| Deployment | Docker Compose for every kit. Terraform modules and Kubernetes manifests provide cloud-neutral starting points; provider-specific modules are on the roadmap. |
| Operator console | Vue application ([`console/`](console/)) presenting the assessment, control, rollout and recovery views with seeded sample tenants. |

The [roadmap](ROADMAP.md) lists what the next releases add; the [changelog](CHANGELOG.md) lists what each release shipped.

## Quick start

Each kit has its own quick start. The shortest path to see all three working:

```bash
# Identity and MFA — http://localhost:8880
cd identity-mfa && docker compose up -d

# Logging and Alerts — Kibana http://localhost:5601, Prometheus http://localhost:9090
cd ../logging-alerts && docker compose up -d

# Backup and Recovery Drills — runs the full verified drill and writes a report under reports/
cd ../backup-dr && ./tests/acceptance.sh
```

Requirements: Docker with Compose v2. Host PHP, Node and database clients are optional and only needed for working on the code outside containers.

## Reference guidance

The kits turn widely used guidance for smaller organizations into working configuration and checks:

- NIST CSF 2.0 and the [Small Business Quick-Start Guide (NIST SP 1300)](https://csrc.nist.gov/pubs/sp/1300/final) — outcome structure used in the control mapping.
- [NIST SP 800-207, Zero Trust Architecture](https://csrc.nist.gov/pubs/sp/800/207/final) — per-request authentication and least-privilege design in the identity kit.
- [CISA Cross-Sector Cybersecurity Performance Goals](https://www.cisa.gov/cybersecurity-performance-goals) — prioritization of MFA, failed-login detection, log collection and tested backups.

## Reproducing a kit and reporting results

[`docs/REPRODUCING.md`](docs/REPRODUCING.md) walks through setting up a kit from a tagged release, running its acceptance checks, and recording the results. Operators who complete it can share their results through the **Reproduction report** issue form; reports with the version used, environment, deviations and the help needed are what improve the kits for the next team.

## Contributing

Issues and pull requests are welcome. Keep changes focused, documented, and reproducible from a clean checkout; CI must pass. See [docs/DEVELOPMENT-HANDBOOK.md](docs/DEVELOPMENT-HANDBOOK.md) for layout and conventions.

## Security

Report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## License and citation

Apache License 2.0 — see [LICENSE](LICENSE). To cite the project, use [CITATION.cff](CITATION.cff).

Maintainer: **Alexander Yarovoy**.
