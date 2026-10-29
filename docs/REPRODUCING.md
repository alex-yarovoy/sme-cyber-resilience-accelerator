# Reproducing a kit

This guide takes an operator from a clean machine to a verified kit, using only the published instructions, and records the result in a form other teams can compare. Plan for 1–2 hours per kit.

## 1. Pick a release

Work from a release tag, not from `main`, so your results refer to a fixed version:

```bash
git clone https://github.com/alex-yarovoy/sme-cyber-resilience-accelerator.git
cd sme-cyber-resilience-accelerator
git checkout v0.1.0          # or the latest tag on the Releases page
git rev-parse HEAD           # record this commit
```

Record your environment: operating system, CPU architecture, Docker and Compose versions (`docker version`, `docker compose version`), and whether you run locally, in a VM, or in a cloud account.

## 2. Run the kit

Follow the kit's own README from top to bottom without help first. Note every point where you had to guess, search, or deviate.

| Kit | Instructions | Acceptance checks |
|-----|--------------|-------------------|
| Identity and MFA | [`identity-mfa/README.md`](../identity-mfa/README.md) | `cd identity-mfa/backend && APP_ENV=test vendor/bin/phpunit` (requires the Compose stack or local PostgreSQL and Redis) |
| Logging and Alerts | [`logging-alerts/README.md`](../logging-alerts/README.md) | `tests/redaction_check.sh` and `promtool test rules tests/auth.rules.test.yml` |
| Backup and Recovery Drills | [`backup-dr/README.md`](../backup-dr/README.md) | `tests/acceptance.sh` |

## 3. Verify the controls

Use the matching section of the [control-to-evidence checklist](CONTROL-MAPPING.md). For each control in scope, run the "How to verify" step and note the result: works as documented, works with changes, or does not work.

## 4. Record what happened

Keep:

- the commit you tested and your environment;
- acceptance check output (for the backup kit, the JSON drill report under `backup-dr/reports/`);
- the time from clone to all checks passing;
- every deviation from the instructions and why;
- the help you needed: none, documentation only, written Q&A with the maintainer, or a live session;
- whether this was an evaluation (lab or staging) or a deployment serving real users.

## 5. Share the report

Open a **Reproduction report** issue (Issues → New issue → Reproduction report) and paste the items above. You can share results without naming your organization; say in the form how you want to be credited. Problems you hit become issues for the next release.
