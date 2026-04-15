# Security policy

## Supported versions

This is a **reference / lab-oriented** monorepo. Only the latest commit on the default branch (`main`) receives routine maintenance (documentation, dependency bumps, CI checks). Tags may be added for reproducible snapshots; use the newest tag when evaluating for production adaptation.

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security-sensitive reports.

1. Open a **[GitHub Security Advisory](https://github.com/alex-yarovoy/sme-cyber-resilience-accelerator/security/advisories/new)** for this repository (private disclosure to maintainers), **or**
2. Email the repository owner at the address associated with their GitHub profile if you cannot use advisories.

Include: affected paths (for example `identity-mfa/`, `backup-dr/scripts/`), reproduction steps, and impact assessment if known.

## Scope notes

- The **logging-alerts** Docker Compose stack ships with **Elasticsearch security disabled** for local convenience. That configuration is **intentionally not** suitable for internet exposure.
- **Backup scripts** can perform **destructive** restores (`DROP SCHEMA public CASCADE`). Treat them as **lab tooling** unless you have reviewed them for your environment.

## Response expectations

This is an open-source side project; there is no SLA. Critical fixes are addressed as time permits. For production systems, fork the repository, apply your own hardening, and run your own security review.
