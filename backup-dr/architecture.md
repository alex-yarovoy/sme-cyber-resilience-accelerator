# Architecture

## In repository today

```
[PostgreSQL] --pg_dump--> [gzip .sql.gz] --> [optional OpenSSL encrypt]
                              |
                              +--> [optional user hook: S3 / MSP storage]
[MySQL/MariaDB] --mysqldump--> [gzip .sql.gz]  (same script interface as Postgres)
[dr_drill.sh] --> [ephemeral Postgres in Docker] --> [psql restore] --> [sanity SQL] --> [report file]
```

## Planned (see repository ROADMAP)

- Object storage + Object Lock and KMS patterns as **Infrastructure as Code** (Terraform provider modules), not hand-run secrets in git.
- Optional ClamAV (or other) scanning integrated in CI and in drill reports when tooling is present.
