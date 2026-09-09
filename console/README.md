# Operator console

Vue app for reviewing assessment, KPI, identity, detection, recovery, and architecture surfaces against a **seeded sample tenant**.

This UI is Compose-independent. Wiring these surfaces to live identity, logging, and backup telemetry is on the [root roadmap](../ROADMAP.md).

## Run

```bash
cd console
npm ci
npm run dev
```

Vite listens on **port 5174**.

```bash
npm run test
```

runs the Vitest suite (`npx vitest run`).

## Routes

| Path | Name |
|------|------|
| `/` | `overview` |
| `/assessment` | `assessment` |
| `/controls` | `controls` |
| `/rollout` | `rollout` |
| `/identity` | `identity` |
| `/audit` | `audit` |
| `/detection` | `detection` |
| `/recovery` | `recovery` |
| `/architecture` | `architecture` |
| `/zero-trust` | `zeroTrust` |
| `/clients` | `clients` |
| `/playbooks` | `playbooks` |

## Seeded sample tenants

Ids in `src/fixtures/sampleTenants.ts`: `cedar-harbor`, `brightcart`, `oak-ember`, `northline`. The default selection is Cedar Harbor Inn.
