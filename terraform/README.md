# Terraform layout (cloud-neutral skeleton)

This tree defines **composable modules** and an **example root module**. It does not pin a cloud provider in v1: wire your provider block in `environments/<name>/` when you choose AWS, GCP, Azure, or another platform.

## Layout

- `modules/network/` — CIDR, subnets, routing (abstract outputs)
- `modules/object_storage/` — versioned object storage pattern (S3-compatible API)
- `modules/postgres/` — managed relational database pattern
- `modules/identity_mfa/` — composition example for the identity component
- `environments/example/` — validates with `terraform validate` in CI (no apply)

## Remote state

Configure a remote backend in your environment root when ready (Terraform Cloud, S3 + DynamoDB, GCS, Azure Storage, etc.). The example environment uses local state only.

## Usage

```bash
cd environments/example
terraform init -backend=false
terraform validate
```

Provider implementations and variable defaults for a specific cloud belong in follow-on changes under `environments/` without changing module boundaries.
