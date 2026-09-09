import type { SampleTenant } from './types'

const cedarHarbor: SampleTenant = {
  id: 'cedar-harbor',
  name: 'Cedar Harbor',
  sector: 'hospitality',
  msp: 'Northstar Managed',
  rolloutDay: 1,
  csf: {
    govern: 0,
    identify: 0,
    protect: 0,
    detect: 0,
    respond: 0,
    recover: 0,
  },
  mfaPercent: 0,
  passkeyPercent: 0,
  misconfigsCurrent: 0,
  misconfigsTarget: 0,
  misconfigsBaseline: 0,
  logCoveragePercent: 0,
  mttdHours: 0,
  mttrHours: 0,
  rtoHours: 0,
  rpoHours: 0,
  lastDrillAt: '2026-01-01T00:00:00.000Z',
  lastDrillHours: 0,
  lastDrillPassed: false,
  kmsSecretsPercent: 0,
  assetCount: 0,
}

export const sampleTenants: [SampleTenant, ...SampleTenant[]] = [cedarHarbor]
