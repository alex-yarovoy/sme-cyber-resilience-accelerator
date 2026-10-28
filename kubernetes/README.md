# Kubernetes manifests (Kustomize)

Start with **identity-mfa**; logging and backup patterns are documented per component.

## identity-mfa

```bash
kubectl apply -k kubernetes/identity-mfa/overlays/dev
```

Probes use `GET /api/health` on the nginx service port. Set secrets (`DATABASE_URL`, `JWT_*`, `REDIS_URL`) via your cluster secret manager before production overlays.

## logging-alerts

The full Elastic stack is often run on managed Elasticsearch or a dedicated VM. Use Compose in `logging-alerts/` for lab parity; see `logging-alerts/README.md` before colocating ES on Kubernetes.

## backup-dr

See `backup-dr/cronjob/` for a CronJob template that invokes backup scripts from a container with database client tools.
