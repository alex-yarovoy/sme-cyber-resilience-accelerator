# Changelog

All notable changes are recorded here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [semantic versioning](https://semver.org/).

## [0.1.0] - 2026-10-29

First tagged release of the three kits, the operator console, and their acceptance checks.

### Added

- **Backup and Recovery Drills:** verified restore drill (`backup-dr/tests/acceptance.sh`) — backup, encrypt, decrypt, restore into a clean database, per-table row-count and checksum comparison, and a timed text and JSON report recording elapsed restore time and backup age. Runs in CI and uploads the report as a build artifact.
- **Logging and Alerts:** `sme-redact` ingest pipeline now masks credentials in key-value and JSON form and card numbers in addition to bearer tokens and emails; a `pipeline-setup` service loads it and Filebeat routes every event through it.
- **Logging and Alerts:** redaction acceptance check (`tests/redaction_check.sh`, `tests/redaction-cases.json`) and `promtool` unit tests for every alert rule.
- **Logging and Alerts:** `EndpointDown` availability alert on the blackbox HTTP probe.
- **Identity and MFA:** PHPUnit coverage for MFA-gated login, TOTP verification, and login rate limiting.
- CI jobs for the console (tests and build), the backup drill, redaction cases and alert rules.
- [Control-to-evidence checklist](docs/CONTROL-MAPPING.md) mapping every control to NIST CSF 2.0 outcomes, implementation, evidence and verification.
- [Reproduction guide](docs/REPRODUCING.md) and a **Reproduction report** issue form.
- `CITATION.cff`, this changelog, and a versioned [roadmap](ROADMAP.md).

### Fixed

- **Identity and MFA:** TOTP verification built its configuration with arguments in the wrong order, so every MFA verification failed with HTTP 500; verification now succeeds for valid codes (covered by `MfaFlowTest`).
- **Identity and MFA:** the MFA verification endpoint now accepts only MFA-pending tokens and consumes each one atomically, so a used or differently typed token is rejected (covered by `MfaFlowTest`).
- Failed-login and MFA-failure alert thresholds were evaluated per second while documented per minute; rules now compare per-minute rates.
- `terraform/` modules and example environment are now tracked in git, so the Terraform validation job runs.
- `composer.lock` resolves every package from Packagist, so dependency installation works on any machine.

### Changed

- README, roadmap and security policy rewritten around the three kits, their acceptance checks and release status.

## Earlier development (April–October 2026)

Initial implementation of the identity, logging and backup kits, Compose stacks, Terraform and Kubernetes starting points, and the operator console. See the git history for details.

[0.1.0]: https://github.com/alex-yarovoy/sme-cyber-resilience-accelerator/releases/tag/v0.1.0
