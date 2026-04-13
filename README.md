# SME resilience kits

Reference implementations for **managed service providers (MSPs)** and product teams that need **repeatable**, **vendor-neutral** baselines on common stacks: strong identity, observable operations, and tested recovery.

The work targets **practical cyber-resilience** for small and mid-sized enterprises—especially teams with limited dedicated security staff—by turning goals such as **identity and least privilege**, **data protection**, **observability**, and **recoverability** into day-to-day engineering artifacts (configs, scripts, runbooks), not slide-deck advice alone.

## Three kits

The repository is a **monorepo of three independent kits**. There is no shared runtime between them; they are related by theme (prevent abuse, detect regressions, recover with evidence).

| Kit | Path | Stack (summary) | Primary outcomes |
|-----|------|-----------------|------------------|
| Identity + MFA | [`identity-mfa/`](identity-mfa/) | Symfony, Vue, PostgreSQL, Redis | Account security, MFA, auditable auth events |
| Logging + Alerts | [`logging-alerts/`](logging-alerts/) | Elastic Stack, Prometheus, Alertmanager | Searchable logs, metrics, routed alerts |
| Backup + DR drill | [`backup-dr/`](backup-dr/) | PostgreSQL (scripts), S3-style storage patterns | Encrypted backups, restore verification, drill-oriented runbooks |

Each kit folder has its own `README.md` (and, where present, `architecture.md` or `ARCHITECTURE.md`) with stack details, assumptions, and quick start steps. Mermaid diagrams live under each kit’s `diagrams/` folder.

## How the pieces compose

1. **Identity** defines who is in the system and under what policy (MFA, sessions, audit-friendly events).  
2. **Logging and alerts** make failures and abuse visible early and route signal instead of noise.  
3. **Backup and drills** ensure recovery is **practiced**, not guessed—encrypted, versioned backups with documented **RTO** and **RPO** expectations where you define them.

Together they close a loop: **prevent** credential abuse, **detect** operational and security regressions, **recover** with evidence that restore paths work.

## Who this is for

- Engineering leads standardizing security and reliability across many small or mid-sized deployments  
- MSPs onboarding clients who need a clear baseline before customization  
- Developers learning production-shaped patterns (not toy demos)

## Principles

- **Portable defaults** — Prefer S3-compatible storage APIs, standard SQL, and common auth flows so teams can change hosting or cloud without rewriting the kit.  
- **Operational honesty** — Proof-of-concept placeholders, local-dev-only security settings, and integration gaps should stay visible in kit docs and code comments rather than implied as production-complete.  
- **No secrets in git** — Use environment variables and `.env` (not committed); compose files use **dev-only** placeholders—rotate for any shared environment.  
- **Observable security** — Authentication and risk-related decisions should be **auditable** where the identity kit implements logging.  
- **Recoverability is tested** — Backup scripts are meant to pair with **restore** and **drill** scripts; a backup without a tested restore is incomplete.  
- **Single responsibility per kit** — Keep kit boundaries clear so teams can reuse one slice without adopting the whole repo.

## Non-goals (current phase)

These kits are **not** a full replacement for enterprise identity providers, a FedRAMP or PCI-DSS certification claim for this repository alone, or one turnkey multi-cloud Terraform product covering all three areas.

## Contributing

Follow the license file in each kit where present. Report issues and propose changes via pull requests; keep commits focused and reproducible.
