# Architecture

```
[DB (Postgres/MySQL)] -> [Encrypted Dumps] -> [Offsite Storage (S3-compatible)]
[Configs/Secrets] -> [Encrypted Vault]
[DR Drill Runner] -> [Provision Temp Env] -> [Restore] -> [Automated Tests] -> [Report]
```


