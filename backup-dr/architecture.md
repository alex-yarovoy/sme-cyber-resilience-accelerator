# Architecture

## Phase 0 (in repository)

```
[PostgreSQL] --pg_dump--> [gzip .sql.gz] --> [optional OpenSSL encrypt]
                              |
                              +--> [optional user hook: S3 / MSP storage]
[dr_drill.sh] --> [ephemeral Postgres in Docker] --> [psql restore] --> [sanity SQL] --> [report file]
```

## Planned (see repository ROADMAP)

- MySQL/MariaDB equivalents alongside Postgres.
- Object storage + Object Lock and KMS patterns as **Infrastructure as Code** (Terraform), not hand-run secrets in git.
- Optional ClamAV (or other) scanning integrated in CI and in drill reports when tooling is present.
