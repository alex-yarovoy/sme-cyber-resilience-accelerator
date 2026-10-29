# Security policy

## Supported versions

| Version | Supported |
|---------|-----------|
| Latest release tag | Yes |
| `main` | Yes — fixes land here first |
| Older tags | Upgrade to the latest release |

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security-sensitive reports.

1. Open a **[GitHub Security Advisory](https://github.com/alex-yarovoy/sme-cyber-resilience-accelerator/security/advisories/new)** for this repository (private disclosure to the maintainer), **or**
2. Email the repository owner at the address on their GitHub profile if you cannot use advisories.

Include the affected paths (for example `identity-mfa/`, `backup-dr/scripts/`), reproduction steps, and the impact if known.

## Response

- Acknowledgement within 5 business days.
- Assessment and, for confirmed issues, a fix or mitigation plan within 30 days; critical issues are prioritized.
- Fixed issues are listed in the [changelog](CHANGELOG.md) with credit to the reporter unless anonymity is requested.

## Deployment notes

- The **logging-alerts** Compose profile runs Elasticsearch without authentication so the stack starts on a workstation with no setup. Before any shared or networked use, enable security and TLS as described in [`logging-alerts/README.md`](logging-alerts/README.md).
- **Restore scripts** replace the target schema (`DROP SCHEMA public CASCADE`). Point them only at a database you intend to overwrite; the verified drill in `backup-dr/tests/` always restores into a fresh container.
- Compose files ship development credentials. Replace them with secrets from your secret manager for every non-local environment.
