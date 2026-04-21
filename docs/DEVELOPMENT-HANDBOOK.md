# SME Cyber Resilience Accelerator — Development Handbook

**Purpose of this document:** Onboarding and continuation guide for engineers (or AI assistants) who have **no prior context** for this repository. It describes layout, shipped behavior, operating principles, known gaps, and a prioritized backlog.

**Repository root:** Monorepo-style folder with three independent **components** (subprojects). There is **no** shared runtime between them; they are thematically related (identity, observability, recovery).

**Canonical high-level docs in repo:**

- Root [`README.md`](../README.md) — audience and quick map  
- [`ROADMAP.md`](../ROADMAP.md) — planned Terraform, Kubernetes, and near-term work  

---

## 1. Vision and principles

### 1.1 Product intent

Deliver **production-style baselines** for small and mid-sized teams and MSPs on **common stacks** (PHP/Symfony, Vue, PostgreSQL, Redis, Elastic stack, Prometheus, Docker) so security and reliability improvements are **operational** in code and automation, not limited to documentation alone.

### 1.2 Engineering principles

1. **Vendor-neutral defaults** — Prefer portable APIs (S3-compatible storage, standard SQL, OpenID-style flows) so teams can swap cloud or hosting without rewriting the component.  
2. **Traceable backlog** — Document remaining hardening and integration work in issues, this handbook, and **[ROADMAP.md](../ROADMAP.md)**; ship improvements in focused changes.  
3. **Secrets never in git** — Use environment variables and `.env` (not committed); compose files in repo use **dev-only** sample values.  
4. **Observable security** — Auth and risk decisions should be **auditable** (audit log entity and events in the identity component).  
5. **Recoverability is tested** — Backup scripts are paired with **restore** and **drill** scripts; a backup without a tested restore is incomplete.  
6. **Single responsibility per component** — Do not merge the three stacks into one deployable monolith; keep boundaries clear for reuse.

### 1.3 Non-goals (current phase)

- Replacing a full identity provider (Okta, Keycloak) in large enterprises.  
- FedRAMP or full PCI-DSS certification as a claim for this repo alone.  
- One-click multi-cloud Terraform for all three stacks (tracked on [ROADMAP.md](../ROADMAP.md)).

---

## 2. Repository map

```
.
├── README.md
├── ROADMAP.md
├── LICENSE
├── SECURITY.md
├── identity-mfa/
├── logging-alerts/
├── backup-dr/
└── docs/
    └── DEVELOPMENT-HANDBOOK.md   # this file
```

**Diagrams (Mermaid):**

- `identity-mfa/diagrams/auth-flow.mmd`  
- `logging-alerts/diagrams/logging-flow.mmd`  
- `backup-dr/diagrams/backup-strategy.mmd`  

---

## 3. Identity + MFA — `identity-mfa/`

### 3.1 What exists today

| Layer | Technology | Notes |
|-------|------------|--------|
| HTTP | Nginx → PHP-FPM | [`docker-compose.yml`](../identity-mfa/docker-compose.yml), [`configs/nginx.conf`](../identity-mfa/configs/nginx.conf); API under `/api/`. |
| Backend | Symfony 6.4, PHP ≥ 8.1 | [`backend/composer.json`](../identity-mfa/backend/composer.json). |
| Auth | Lexik JWT, Gesdinet refresh bundle, scheb/2fa TOTP | User entity implements `TwoFactorInterface`. |
| Passkeys | `web-auth/webauthn-symfony-bundle` | Thin wrapper [`WebAuthnController`](../identity-mfa/backend/src/Controller/WebAuthnController.php). |
| Data | PostgreSQL 14, Redis 7 | UUID users; migrations under `backend/migrations/`. |
| Frontend | Vue 3, Vite, Pinia, Axios, Vuetify | Admin shell in [`frontend/src/App.vue`](../identity-mfa/frontend/src/App.vue). |

**Implemented HTTP flows (custom):**

- `POST /api/auth/login` — password check, rate limit, optional MFA step-up, JWT + refresh (see [`AuthController`](../identity-mfa/backend/src/Controller/AuthController.php)).  
- `POST /api/auth/mfa/verify` — TOTP verification after short-lived MFA token.  
- `POST /api/auth/refresh` — **also** declared in [`routes.yaml`](../identity-mfa/backend/config/routes.yaml) for Gesdinet at same path — **potential route clash** with `AuthController::refresh` (see section 7).  
- `POST /api/auth/logout`, `GET /api/auth/me`.  
- WebAuthn routes under `/api/webauthn/*` (wrappers around bundle controllers).

**Services of note:**

- [`JwtTokenService`](../identity-mfa/backend/src/Service/JwtTokenService.php) — HS256-style secret from params; MFA and refresh payloads; **token denylist** currently returns `false` for `isTokenBlacklisted` until Redis-backed revocation is wired (see §7 D3).  
- [`RiskScoringService`](../identity-mfa/backend/src/Service/RiskScoringService.php) — **context-based** scoring from `$context` flags; optional IP reputation or device signals are extension points for a later iteration.  
- [`AuditLogger`](../identity-mfa/backend/src/Service/AuditLogger.php), [`RateLimiterService`](../identity-mfa/backend/src/Service/RateLimiterService.php), [`RefreshTokenGenerator`](../identity-mfa/backend/src/Service/RefreshTokenGenerator.php).

**Seed users** ([`AppFixtures`](../identity-mfa/backend/src/DataFixtures/AppFixtures.php)):

- `admin@example.com` / `Admin#123456` — ROLE_ADMIN, MFA off.  
- `user@example.com` / `User#12345678` — ROLE_USER.

### 3.2 What `ARCHITECTURE.md` in this kit is

[`identity-mfa/ARCHITECTURE.md`](../identity-mfa/ARCHITECTURE.md) describes long-term architecture and may include classes (for example `MfaAuthenticationProvider`) that have not landed in `src/` yet—reconcile documentation with each release so the doc matches shipped code.

### 3.3 Frontend notes

- [`App.vue`](../identity-mfa/frontend/src/App.vue) is a **focused** login → MFA → “logged in” flow with default dev credentials for local use.  
- Client-side WebAuthn ceremony: finish per W3C WebAuthn (ArrayBuffer handling and `navigator.credentials`) when exposing passkeys in the SPA.  
- [`http.ts`](../identity-mfa/frontend/src/http.ts) uses `baseURL: '/api'` — assumes dev proxy or same-origin Nginx serving both SPA and API.

### 3.4 How to run (development)

**Docker (recommended first path):**

```bash
cd identity-mfa
docker compose up -d --build
```

Then inside PHP container (or local PHP with same env):

- `composer install`  
- `php bin/console doctrine:migrations:migrate --no-interaction`  
- `php bin/console doctrine:fixtures:load --no-interaction` (dev only), or set `LOAD_LAB_FIXTURES=1` before first boot (see `backend/docker-entrypoint.sh`).

**Ports (from compose):**

- Nginx: `8080` → 80 inside  
- Postgres host port: `5434` → 5432  
- Redis: `6379`

**Environment:** `DATABASE_URL`, `REDIS_URL`, `JWT_SECRET` in compose are **non-production sample values**; rotate for any shared environment.

**Frontend:**

```bash
cd identity-mfa/frontend
npm install
npm run dev
```

Vite dev server must proxy `/api` to the backend (or use Nginx-served build) — **verify `vite.config` proxy** if API calls fail from `localhost:5173`.

### 3.5 Tests

`backend/tests/` is **empty** in the current tree; `composer.json` includes PHPUnit and Codeception. **Backlog:** add API functional tests for login, MFA, refresh, and WebAuthn happy paths.

---

## 4. Logging + alerts — `logging-alerts/`

### 4.1 What exists today

Docker Compose stack ([`docker-compose.yml`](../logging-alerts/docker-compose.yml)):

- Elasticsearch 8.12.2 (single-node, **security disabled** for local dev).  
- Kibana 8.12.2.  
- Filebeat 8.12.2 mounting [`configs/filebeat.yml`](../logging-alerts/configs/filebeat.yml) and host path `./configs/logs/` as `/var/log/app/`.  
- Prometheus 2.53 + Alertmanager 0.27.

**Configs:**

- [`filebeat.yml`](../logging-alerts/configs/filebeat.yml) — JSON logs from `/var/log/app/*.log`.  
- [`prometheus.yml`](../logging-alerts/configs/prometheus.yml) — self-scrape + **blackbox** job targeting `nginx:80`.  
- [`alertmanager.yml`](../logging-alerts/configs/alertmanager.yml) — receivers (review file).  
- [`ingest-pipeline.json`](../logging-alerts/configs/ingest-pipeline.json) — PII-oriented ingest (per [`architecture.md`](../logging-alerts/architecture.md)).  
- [`alert-rules/auth.rules.yml`](../logging-alerts/alert-rules/auth.rules.yml) — expects metrics `app_auth_login_failed_total`, `app_auth_mfa_failed_total` — **these series are not produced by this compose file** unless an instrumented app exports them.

### 4.2 Known integration gaps

1. **No `nginx` service** in this compose — Prometheus blackbox target `nginx:80` will fail until you add an nginx service or change targets.  
2. **Metrics from identity component** are not wired — connect Symfony/Prometheus exporter or pushgateway in a future iteration.  
3. Elasticsearch `xpack.security.enabled=false` is **dev only**; document hardening for any non-local use.

### 4.3 How to run

```bash
cd logging-alerts
mkdir -p configs/logs
# add sample JSON lines into configs/logs/app.log if needed
docker compose up -d
```

Ports: Elasticsearch `9200`, Kibana `5601`, Prometheus `9090`, Alertmanager `9093`.

### 4.4 Ideas for next steps

- Add a **lightweight `nginx` + static** health service to satisfy blackbox scrapes.  
- Add **OpenTelemetry** or Prometheus PHP client in identity-mfa to emit the counters referenced in alert rules.  
- Commit a **log fixture generator** script for integration and load tests.  
- Add retention and ILM policy examples matching [`architecture.md`](../logging-alerts/architecture.md).

---

## 5. Backup + DR — `backup-dr/`

### 5.1 What exists today

| Asset | Role |
|-------|------|
| [`scripts/backup_postgres.sh`](../backup-dr/scripts/backup_postgres.sh) | `pg_dump` → gzip under `backups/`, SHA256 sidecar. Env: `DB_HOST`, `DB_USER`, `DB_NAME`. |
| [`scripts/restore_postgres.sh`](../backup-dr/scripts/restore_postgres.sh) | Restore pipeline (read script for exact usage). |
| [`scripts/encrypt_backup.sh`](../backup-dr/scripts/encrypt_backup.sh) | `openssl enc -aes-256-cbc -pbkdf2`; requires `BACKUP_ENC_PASS`. |
| [`scripts/decrypt_backup.sh`](../backup-dr/scripts/decrypt_backup.sh) | Decrypt companion. |
| [`scripts/dr_drill.sh`](../backup-dr/scripts/dr_drill.sh) | Spins ephemeral `postgres:14` in Docker, restores from `.sql.gz`, runs `SELECT 1`, writes report under `reports/`. |
| [`tests/dr_sanity.sh`](../backup-dr/tests/dr_sanity.sh) | Simple `psql` connectivity check. |
| [`architecture.md`](../backup-dr/architecture.md) | ASCII data flow. |

### 5.2 Portability note

`dr_drill.sh` uses `date -d @$START` for report timestamps — **GNU date** syntax. On **macOS BSD `date`**, this fails; use GNU `gdate` or replace with portable formatting (`date -r "$START"` on macOS). **Track as cross-platform fix.**

### 5.3 README claims versus repo

Root [`backup-dr/README.md`](../backup-dr/README.md) mentions MySQL scripts, S3 Object Lock, ClamAV — **verify** each exists under `scripts/` before claiming in external docs; as of handbook authorship, the **listed scripts above** are the core verified set.

### 5.4 Ideas for next steps

- Add **MySQL** mirror scripts with same interface as Postgres.  
- Add **rclone** or AWS CLI push step to S3-compatible bucket after backup.  
- Integrate **drill report** JSON for CI (pass or fail gate).  
- Optional **Terraform** module only after bash paths are stable.

---

## 6. Cross-component consistency conventions

When extending any component:

1. **Naming:** Use `kebab-case` for shell scripts and repo folders; PHP follows PSR-4 `App\` under `identity-mfa/backend/src`.  
2. **Logging field names:** Prefer `timestamp`, `level`, `service`, `trace_id`, `message` (see `logging-alerts/architecture.md`) so Filebeat JSON mapping stays stable.  
3. **Version pins:** Prefer explicit image tags (already done in compose) over `latest`.  
4. **Documentation:** Update **this handbook** when you add a new service, change ports, or fix a documented gap.  
5. **Secrets in git:** Do not commit Lexik JWT `*.pem`, real `.env` files, backup dumps, or Filebeat log drops with live data. See root `.gitignore`, `identity-mfa/backend/.gitignore`, `logging-alerts/.gitignore`, and `backup-dr/.gitignore`.

---

## 7. Known issues and technical debt (actionable)

| ID | Area | Description | Suggested fix |
|----|------|-------------|---------------|
| D1 | identity-mfa | ~~`routes.yaml` duplicate refresh route~~ | **Done:** only `AuthController::refresh` serves `POST /api/auth/refresh`; Gesdinet YAML route removed. Refresh uses persisted Gesdinet `RefreshToken` rows from `RefreshTokenGenerator`. |
| D2 | identity-mfa | ~~`PUBLIC_ACCESS` for refresh~~ | **Done:** `access_control` includes `^/api/auth/refresh`; login firewall pattern narrowed to `^/api/auth/(login|refresh|mfa(/.*)?)$` so `/api/auth/me` and `/api/auth/logout` use the JWT `api` firewall. |
| D3 | identity-mfa | JWT blacklist and refresh rotation not backed by Redis despite comments. | Implement Redis-backed denylist and refresh family ID. |
| D4 | identity-mfa | `RiskScoringService` uses only static context flags. | Integrate optional IP reputation or device cookie with safe defaults. |
| D5 | identity-mfa | WebAuthn frontend incomplete. | Complete CBOR/ArrayBuffer handling; align with bundle expected JSON. |
| D6 | logging-alerts | Prometheus scrapes `nginx:80` but no nginx in compose. | Add service or remove job until defined. |
| D7 | logging-alerts | Alert metrics not emitted. | Instrument the identity app or add a small Prometheus exporter that emits the series expected by rules. |
| D8 | backup-dr | GNU `date` in drill report. | Portable date or document Linux-only. |
| D9 | docs | `identity-mfa/README.md` contains strong marketing metrics. | Harmonize with evidence-backed wording or mark as design targets. |

---

## 8. Suggested backlog (prioritized)

**P0 — correctness and security**

1. ~~Resolve refresh route and firewall `PUBLIC_ACCESS` behavior (D1, D2).~~ **Done** (see §7 D1/D2).  
2. Remove or replace dev `JWT_SECRET` and database passwords for any non-local deploy (use `.env` / secrets manager; see `identity-mfa/backend/.env.example`).  
3. Enable Elasticsearch security or isolate stack to offline lab (see `logging-alerts/README.md` — dev stack ships with security disabled).

**P1 — credibility and maintainability**

4. PHPUnit or Codeception tests for AuthController paths.  
5. Wire the first set of Prometheus metrics from the identity component to satisfy `auth.rules.yml` or narrow rules to existing metrics.  
6. Fix `dr_drill.sh` portability (D8).

**P2 — product completeness**

7. Finish WebAuthn E2E (D5).  
8. Vite proxy and production build story for Vue + Nginx.  
9. MySQL backup or restore sibling scripts in backup-dr.  
10. Reconcile `ARCHITECTURE.md` with actual classes or split into `ARCHITECTURE-TARGET.md` vs `CURRENT.md`.

---

## 9. Quick “new session” prompt block

You can paste the block below into another AI session together with this file:

```
You are continuing work on the SME Cyber Resilience Accelerator monorepo (three folders: identity-mfa, logging-alerts, backup-dr).
Read docs/DEVELOPMENT-HANDBOOK.md first. It is the source of truth for layout, principles, gaps, and backlog.
Do not assume features exist unless the handbook or code shows them. Prefer small PR-sized changes; run Docker builds where relevant.
```

---

## 10. Maintainer and contact

Primary maintainer: **Alexander Yarovoy**.

**Document version:** 1.0 (update when the tree materially changes.)
