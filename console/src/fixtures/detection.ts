import { sampleTenants } from './sampleTenants'
import type { AlertRoute, DetectionSnapshot } from './types'

export const incidentRateTargetLabel = 'Incident rate target −30%'

const extras: Record<string, { failedLoginCount: number; alertRoutes: AlertRoute[] }> = {
  'cedar-harbor': {
    failedLoginCount: 3,
    alertRoutes: [
      { channel: 'Email', status: 'active' },
      { channel: 'Slack', status: 'active' },
      { channel: 'Webhook', status: 'active' },
    ],
  },
  brightcart: {
    failedLoginCount: 11,
    alertRoutes: [
      { channel: 'Email', status: 'active' },
      { channel: 'Slack', status: 'active' },
      { channel: 'Webhook', status: 'degraded' },
    ],
  },
  'oak-ember': {
    failedLoginCount: 2,
    alertRoutes: [
      { channel: 'Email', status: 'active' },
      { channel: 'Slack', status: 'active' },
      { channel: 'Webhook', status: 'active' },
    ],
  },
  northline: {
    failedLoginCount: 18,
    alertRoutes: [
      { channel: 'Email', status: 'active' },
      { channel: 'Slack', status: 'degraded' },
      { channel: 'Webhook', status: 'unconfigured' },
    ],
  },
}

export const detection: DetectionSnapshot[] = sampleTenants.map((tenant) => {
  const extra = extras[tenant.id]
  if (!extra) {
    throw new Error(`missing detection fixture for ${tenant.id}`)
  }
  return {
    tenantId: tenant.id,
    logCoveragePercent: tenant.logCoveragePercent,
    alertRoutes: extra.alertRoutes,
    failedLoginCount: extra.failedLoginCount,
  }
})
