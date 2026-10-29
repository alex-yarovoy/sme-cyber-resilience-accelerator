# Control-to-evidence checklist

Every control in the three kits is listed with the NIST Cybersecurity Framework (CSF) 2.0 outcome it supports, where it is implemented, what evidence it produces, and how to verify it. Use this table to agree scope with a system owner, to plan a rollout, and to check after deployment that each control actually operates.

CSF 2.0 references describe which outcome a control contributes to. They are not a certification or a statement of full coverage; each organization decides which outcomes it targets.

**Status:** **Shipped** — implemented and covered by an automated check in CI. **Shipped (manual check)** — implemented, verified with the documented procedure. **Partial** — part of the control ships; the rest is tracked in [ROADMAP.md](../ROADMAP.md). **Roadmap** — planned for a later release.

## Identity and MFA (`identity-mfa/`)

| Control | CSF 2.0 | Implementation | Evidence produced | How to verify | Status |
|---|---|---|---|---|---|
| Time-based one-time password (TOTP) second factor; access token withheld until MFA succeeds; single-use MFA token | PR.AA-03 | `AuthController`, scheb/2fa-totp | `mfa_required` login response; audit event per MFA attempt | `MfaFlowTest`: MFA-enrolled login returns `mfa_required: true` and no access token; a valid TOTP code returns the access token; an invalid code is rejected and counted | Shipped |
| Risk-based step-up to MFA | PR.AA-03 | `RiskScoringService::needStepUp()` | Risk score recorded with the login audit event | Login from a context that raises the score requires MFA even when the user has not enrolled | Partial |
| Brute-force throttling on login and MFA | PR.AA-03, DE.CM-03 | `RateLimiterService` (Symfony rate limiter, Redis) | HTTP 429 with reset time; `app_auth_login_failed_total`, `app_auth_mfa_failed_total` counters | `MfaFlowTest::testRepeatedFailedLoginsFromOneIpAreRateLimited`, `AuthMetricsServiceTest` | Shipped |
| Password hashing with Argon2id (bcrypt fallback) and password-strength policy | PR.AA-01, PR.DS-01 | `security.yaml` (`algorithm: auto`), `PasswordStrengthValidator` | Hash format in the `user` table | Inspect a stored hash prefix (`$argon2id$`) | Shipped (manual check) |
| Short-lived JWT access tokens, single-use refresh rotation, server-side revocation on logout | PR.AA-01, PR.AA-05 | `JwtTokenService`, `RefreshTokenGenerator`, `JwtDenylistSubscriber`, `TokenDenylistService` | Revoked `jti` in Redis denylist; consumed refresh-token rows | `AuthControllerTest::testLogoutDenylistsAccessToken` and refresh tests | Shipped |
| WebAuthn (passkey) registration and login with server-side challenge binding | PR.AA-03 | `WebAuthnController`, `ChallengeBindingWebAuthnVerifier`, `WebAuthnChallengeStore` | Stored credential metadata; rejected replays of stale challenges | `ChallengeBindingWebAuthnVerifierTest` | Partial — attestation and assertion signature verification on ROADMAP |
| Authentication audit trail | PR.PS-04, DE.CM-03 | `AuditLogger`, `AuditLog` entity, `RequestIdSubscriber` | Audit rows with user, action, IP, user agent, request ID | Query `audit_log` after a login, a failed login and a logout | Shipped (manual check) |
| Health and Prometheus metrics endpoints | DE.CM-09 | `MetricsController` (`/api/health`, `/api/metrics`) | Prometheus text exposition | `PublicEndpointsTest` | Shipped |
| Account inventory export and joiner/mover/leaver procedure | ID.AM-01, PR.AA-01, PR.AA-05 | — | Dated account inventory with privileged and staff accounts listed separately | — | Roadmap |
| Periodic least-privilege access review | PR.AA-05 | Role model (`ROLE_USER`, `ROLE_ADMIN`) | Signed-off review record | — | Roadmap |

## Logging and Alerts (`logging-alerts/`)

| Control | CSF 2.0 | Implementation | Evidence produced | How to verify | Status |
|---|---|---|---|---|---|
| Central collection of application logs | PR.PS-04, DE.CM-09 | Filebeat → Elasticsearch → Kibana (`docker-compose.yml`, `configs/filebeat.yml`) | Indexed `filebeat-*` documents | Append a JSON line to `configs/logs/`, find it in Kibana Discover | Shipped (manual check) |
| Sensitive-field redaction before indexing (tokens, credentials, card numbers, emails) | PR.DS-01, PR.PS-04 | `configs/ingest-pipeline.json` (`sme-redact`), loaded by the `pipeline-setup` service | Redacted `message` field in every indexed event | `tests/redaction_check.sh` against `tests/redaction-cases.json` | Shipped |
| Alert on failed-login and MFA-failure bursts | DE.CM-03, DE.AE-02 | `alert-rules/auth.rules.yml` | Firing alert with severity label | `promtool test rules tests/auth.rules.test.yml` | Shipped |
| Alert on unavailable HTTP endpoint | DE.CM-09, DE.AE-02 | Blackbox exporter + `EndpointDown` rule | Firing alert per failing instance | `promtool test rules tests/auth.rules.test.yml` | Shipped |
| Alert routing to the on-call channel | DE.AE-06 | Alertmanager (`configs/alertmanager.yml`, email receiver) | Delivered notification | Configure the receiver for your channel, then send a synthetic alert | Partial — receiver per environment |
| Timed synthetic alert exercise (delivery and acknowledgement times) | DE.AE-06, RS.MA-01 | — | Timestamped exercise record | — | Roadmap |
| Incident triage runbook | RS.MA-01, RS.AN-03 | — | Runbook with roles, first-hour checklist, escalation path | — | Roadmap |
| Authenticated, TLS-protected log storage | PR.DS-01, PR.DS-02 | Hardening steps in `logging-alerts/README.md` | Elasticsearch security settings | Follow the README procedure; confirm anonymous access is refused | Partial — default compose profile runs without Elasticsearch security for local use |

## Backup and Recovery Drills (`backup-dr/`)

| Control | CSF 2.0 | Implementation | Evidence produced | How to verify | Status |
|---|---|---|---|---|---|
| Logical backups of PostgreSQL and MySQL | PR.DS-11 | `scripts/backup_postgres.sh`, `scripts/backup_mysql.sh` | Compressed dump | `tests/acceptance.sh` | Shipped |
| Backup encryption at rest (AES-256-CBC, PBKDF2 key derivation) | PR.DS-01, PR.DS-11 | `scripts/encrypt_backup.sh`, `scripts/decrypt_backup.sh` | Encrypted artifact; decryption round-trip | `tests/acceptance.sh` | Shipped |
| Restore into a clean database with integrity verification (per-table row counts and content checksums) | RC.RP-03, RC.RP-05 | `scripts/restore_postgres.sh`, `tests/acceptance.sh` | Drill report (text and JSON) with PASS/FAIL per table | `tests/acceptance.sh`; CI uploads the report as a build artifact | Shipped |
| Measured recovery exercise (elapsed restore time, backup age at restore) | RC.RP-01, PR.DS-11 | `scripts/dr_drill.sh`, `tests/acceptance.sh` | Report fields for elapsed restore seconds and backup age | Read the drill report | Shipped |
| Scheduled backups on Kubernetes | PR.DS-11 | `kubernetes/backup-dr/cronjob/` | CronJob history | `kubectl get cronjob,job` | Partial — template; storage wiring per cluster |
| Off-site, immutable backup copy | PR.DS-11 | — | Object-lock retention settings | — | Roadmap |

## Measurement model

The operator console (`console/`) shows how these controls roll up into program measures — MFA coverage, managed-secret coverage, critical-service event coverage, alert delivery and acknowledgement times, remediation of baseline findings, and restore time and data-loss window — using seeded sample tenants. Each rollout sets its own dated inventory, baseline and measurement window before results are recorded.
