# SME Cyber Resilience Accelerator

Shippable security and resilience building blocks for **managed service providers (MSPs)** and engineering teams that run **small and mid-sized** environments. The Accelerator packages **strong identity**, **observable operations**, and **tested recovery** into runnable software and automation you can deploy, extend, and run in production.

## What it does

- **Identity and access** — MFA, session policy, and audit-friendly authentication flows backed by a real application stack.  
- **Detection and response** — Centralized logs, metrics, and alert routing so incidents surface in your existing on-call or ticketing path.  
- **Backup and continuity** — Encrypted backups, restore procedures, and drill-oriented scripts so recovery is rehearsed, not assumed.

Each component is documented, versioned with the rest of the monorepo, and intended to be **forked or integrated** into your own delivery pipeline.

## Components

The monorepo contains **three independent products** (separate runtimes; no shared service mesh between them). They share a common goal: reduce credential abuse, catch regressions early, and prove that restore paths work.

| Component | Path | Stack (summary) | What you get |
|-----------|------|-----------------|--------------|
| Identity + MFA | [`identity-mfa/`](identity-mfa/) | Symfony, Vue, PostgreSQL, Redis | Account security, MFA, auditable auth events |
| Logging + Alerts | [`logging-alerts/`](logging-alerts/) | Elastic Stack, Prometheus, Alertmanager | Searchable logs, metrics, routed alerts |
| Backup + DR drill | [`backup-dr/`](backup-dr/) | PostgreSQL (scripts), S3-style storage patterns | Encrypted backups, restore verification, drill-oriented runbooks |

Each directory has its own `README.md` plus architecture notes and quick starts where applicable. Diagrams live under each component’s `diagrams/` folder.

## How the pieces fit together

1. **Identity** defines who may access systems and enforces MFA, session rules, and events you can retain for review.  
2. **Logging and alerts** expose failures and abuse early and send **actionable** signal to the right channels.  
3. **Backup and drills** pair encrypted backups with **restore** and **drill** steps and explicit **RTO** / **RPO** targets you configure.

Together they close a loop: **prevent** credential abuse, **detect** operational and security regressions, **recover** with evidence that restore paths work.

## Current shipping surface

Today’s releases center on **Docker Compose**-based deployments, production-style service configuration, and shell automation. That keeps onboarding fast and behavior transparent before you add heavier orchestration.

**Terraform** and **Kubernetes** (Helm and/or Kustomize) packaging are **planned**; see **[ROADMAP.md](ROADMAP.md)** for the phased delivery plan.

## Phase 0 (shipped in this repository)

| Area | What you get |
|------|----------------|
| **Trust** | `LICENSE` (Apache-2.0), `SECURITY.md`, and GitHub Actions: **ShellCheck** on `backup-dr/scripts`, **`docker compose config`** for `identity-mfa` and `logging-alerts`. |
| **Identity + MFA** | Compose stack: entrypoint runs **migrations**; **lab fixtures are stubbed by default** (one `exec` command in kit README). See [`docs/PHASE0_STUBS.md`](docs/PHASE0_STUBS.md). |
| **Logging + alerts** | Compose stack, committed **sample JSON log** under `logging-alerts/configs/logs/`, and a **Discover-first** Kibana workflow. Version-pinned NDJSON dashboards are deferred to [ROADMAP.md](ROADMAP.md). |
| **Backup + DR** | Postgres-centric **backup / encrypt / decrypt / restore** scripts plus a **Docker-based drill** with portable timestamps in the report. MySQL dumps, Object Lock, and Terraform modules are roadmap items, not implied by the scripts today. |

## Alignment (NIST CSF 2.0)

The three components support the familiar **Govern / Identify / Protect / Detect / Respond / Recover** framing in a **descriptive** sense: they provide runnable patterns and documentation hooks; **your** organization defines policies, metrics, and sign-off.

## Who it is for

- Engineering leads standardizing security and reliability across many SME-scale deployments  
- MSPs onboarding clients who need a clear baseline before customization  
- Developers who want production-shaped patterns rather than toy demos

## Principles

- **Portable defaults** — Prefer S3-compatible storage APIs, standard SQL, and common auth flows so you can change hosting or cloud without rewriting the Accelerator.  
- **No secrets in git** — Use environment variables and `.env` (not committed); Compose files ship **dev-only** placeholders—rotate for any shared environment.  
- **Observable security** — Authentication and risk-related decisions should be **auditable** where the identity component implements logging.  
- **Recoverability is tested** — Backup automation is built to pair with **restore** and **drill** scripts; a backup without a tested restore is incomplete.  
- **Clear boundaries** — Each component stays self-contained so you can adopt one without taking the whole monorepo.

## Contributing

Issues and pull requests are welcome; keep changes focused and reproducible from a clean checkout.
