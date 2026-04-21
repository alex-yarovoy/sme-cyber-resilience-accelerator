# Identity + MFA (lab reference)

Symfony + Vue reference implementation for **account security**, **MFA (TOTP)**, **JWT sessions**, **rate limiting**, and **audit-friendly** authentication events. Intended for **local / lab** use and as a forkable baseline—not a certified compliance product.

## Quick start (Docker, recommended)

From `identity-mfa/`:

```bash
docker compose build --no-cache php
docker compose up -d
```

The `php` image entrypoint will:

1. Create a **minimal `.env`** for Docker (Postgres/Redis hostnames, `MAILER_DSN=null`) if missing  
2. Generate Lexik **JWT PEM keys** if missing  
3. Run `composer install` (**`--no-scripts`** on boot to reduce Flex/recipe variance)  
4. Wait for Postgres, run **migrations**  
5. **Stub:** Doctrine **fixtures are skipped by default** (`LOAD_LAB_FIXTURES=0`). Load lab users when you want a full login flow:

```bash
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate
```

If that command fails in your environment, use the pseudocode SQL stub: [`backend/scripts/seed-lab-users.sql.stub`](backend/scripts/seed-lab-users.sql.stub). Repo-wide stub notes: [`../docs/PHASE0_STUBS.md`](../docs/PHASE0_STUBS.md).

Optional: set `LOAD_LAB_FIXTURES=1` in the environment before `up` to **attempt** fixtures on boot (failures are logged and **php-fpm still starts**).

**Application:** [http://localhost:8880](http://localhost:8880) (nginx → PHP; host port **8880** avoids common collisions with `8080`).

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
# Set DATABASE_URL / REDIS_URL for your host; generate JWT keys (see old handbook or openssl commands in repo history)
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

## What this kit demonstrates

- Password hashing (Argon2), CSRF on session flows, structured **audit log** hooks  
- MFA enrollment patterns (scheb/2fa), JWT issuance (Lexik), refresh-token table  
- Rate limiting and brute-force resistance (Symfony rate limiter)  
- Vue 3 + Vite admin UI for login / MFA flows  

## What it does **not** claim

This repository is **not** accompanied by SOC 2, HIPAA, or GDPR certification. Controls are implemented as **patterns** you can extend under your own compliance program.

## API (sketch)

- `POST /api/auth/login` — JSON `email`, `password`  
- MFA and refresh endpoints — see `backend/src/Controller/` for current routes  

## Stack

| Layer    | Technology                          |
|----------|-------------------------------------|
| Backend  | Symfony 6.4, PHP 8.2, PostgreSQL 14 |
| Cache    | Redis 7                             |
| Frontend | Vue 3, TypeScript, Vite             |
| Edge     | nginx (Compose lab only)            |

## Further reading

- [ARCHITECTURE.md](ARCHITECTURE.md) — component and data-flow notes  
- Parent monorepo [README.md](../README.md) — NIST CSF mapping and Phase 0 scope  

Issues and PRs: keep changes reproducible from a clean checkout.
