# Identity + MFA

Symfony + Vue stack for **account security**, **MFA (TOTP)**, **JWT sessions**, **rate limiting**, and **audit-friendly** authentication events. Ships as a runnable service you deploy in **development or production** environments and extend under your own security and compliance program—it is **not** itself a SOC 2 / HIPAA / GDPR certification.

## Quick start (Docker, recommended)

From `identity-mfa/`:

```bash
docker compose build --no-cache php
docker compose up -d
```

The `php` image entrypoint will:

1. Create a generated **`.env`** for Docker (Postgres/Redis hostnames, `MAILER_DSN=null`) if missing  
2. Generate Lexik **JWT PEM keys** if missing  
3. Run `composer install` (**`--no-scripts`** on boot to reduce Flex/recipe variance)  
4. Wait for Postgres, run **migrations**  
5. Doctrine **fixtures are skipped by default** (`LOAD_LAB_FIXTURES=0`) so container restarts stay predictable. Load lab users when you want the full login flow:

```bash
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate
```

If that command fails (for example UUID or dependency issues), follow [`backend/scripts/LAB_USERS_MANUAL.md`](backend/scripts/LAB_USERS_MANUAL.md).

Optional: set `LOAD_LAB_FIXTURES=1` before `docker compose up` so the entrypoint runs fixtures on boot (failures are logged and **php-fpm still starts**).

**Application:** [http://localhost:8880](http://localhost:8880) (nginx → PHP; host port **8880** avoids collisions with common `8080` usage).

### Lab credentials (after fixtures load)

| Role  | Email             | Password       |
|-------|-------------------|----------------|
| Admin | admin@example.com | `Admin#123456` |
| User  | user@example.com  | `User#12345678` |

Stop: `docker compose down` (add `-v` to drop the Postgres volume).

## Quick start (host PHP / without Docker)

Requires PHP 8.2+, Composer, Node 18+, Postgres 14+, Redis 7+.

```bash
cd backend
composer install
cp .env.example .env
# Set DATABASE_URL / REDIS_URL for your host; generate JWT keys per `.env.example`
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:2048
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate

cd ../frontend
npm ci
cp .env.example .env.local   # point VITE_API_URL at your backend
npm run dev
```

## Capabilities

- Password hashing (Argon2), CSRF on session flows, structured **audit log** hooks  
- MFA enrollment patterns (scheb/2fa), JWT issuance (Lexik), refresh-token persistence  
- Rate limiting and brute-force resistance (Symfony rate limiter)  
- Vue 3 + Vite admin UI for login / MFA flows  

## Compliance scope

Third-party attestations (SOC 2, HIPAA, GDPR program) are **your** organizational responsibility. This codebase implements **technical controls as software** that you tailor, test, and operate under that program.

## API overview

- `POST /api/auth/login` — JSON `email`, `password`  
- MFA, refresh, WebAuthn — see `backend/src/Controller/` and `backend/config/routes.yaml`  

## Stack

| Layer    | Technology                          |
|----------|-------------------------------------|
| Backend  | Symfony 6.4, PHP 8.2, PostgreSQL 14 |
| Cache    | Redis 7                             |
| Frontend | Vue 3, TypeScript, Vite             |
| Edge     | nginx (Compose example)             |

## Further reading

- [ARCHITECTURE.md](ARCHITECTURE.md) — component and data-flow notes  
- Parent monorepo [README.md](../README.md) and [docs/DEVELOPMENT-HANDBOOK.md](../docs/DEVELOPMENT-HANDBOOK.md)  

Issues and PRs: keep changes reproducible from a clean checkout.
