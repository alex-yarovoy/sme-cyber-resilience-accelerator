import { remediationPercent, sampleTenants } from './sampleTenants'
import type { ControlRow, CsfFunction, SampleTenant } from './types'

interface ControlSpec {
  id: string
  policyTheme: string
  control: string
  target: number
  unit: string
  csf: CsfFunction
  currentOf: (tenant: SampleTenant) => number | null
}

const specs: ControlSpec[] = [
  { id: 'privileged-mfa', policyTheme: 'Identity', control: 'Privileged interactive accounts with MFA', target: 100, unit: '%', csf: 'protect', currentOf: t => t.privilegedMfaPercent },
  { id: 'staff-mfa', policyTheme: 'Identity', control: 'Other in-scope interactive accounts with MFA', target: 95, unit: '%', csf: 'protect', currentOf: t => t.mfaPercent },
  { id: 'secrets', policyTheme: 'Data protection', control: 'Identified runtime secrets in a managed secret store', target: 100, unit: '%', csf: 'protect', currentOf: t => t.managedSecretsPercent },
  { id: 'logging', policyTheme: 'Logging', control: 'Critical services with verified event collection', target: 90, unit: '%', csf: 'detect', currentOf: t => t.logCoveragePercent },
  { id: 'alert-delivery', policyTheme: 'Detection exercise', control: 'Synthetic alert delivery time', target: 15, unit: 'min', csf: 'detect', currentOf: t => t.alertDeliveryMinutes },
  { id: 'acknowledgement', policyTheme: 'Staffed exercise', control: 'Alert acknowledgement time', target: 60, unit: 'min', csf: 'respond', currentOf: t => t.acknowledgementMinutes },
  { id: 'restore', policyTheme: 'Recovery exercise', control: 'Tested restore duration', target: 4, unit: 'h', csf: 'recover', currentOf: t => t.lastDrillHours },
  { id: 'data-loss', policyTheme: 'Recovery exercise', control: 'Backup age in the sample drill', target: 1, unit: 'h', csf: 'recover', currentOf: t => t.lastBackupAgeHours },
  { id: 'remediation', policyTheme: 'Configuration', control: 'Baseline high and critical findings resolved', target: 60, unit: '%', csf: 'identify', currentOf: remediationPercent },
]

export const controls: ControlRow[] = sampleTenants.flatMap(tenant =>
  specs.map(spec => ({
    id: `${tenant.id}-${spec.id}`, tenantId: tenant.id,
    policyTheme: spec.policyTheme, control: spec.control, kpiLabel: spec.control,
    current: spec.currentOf(tenant), target: spec.target, unit: spec.unit, csf: spec.csf,
    direction: ['alert-delivery', 'acknowledgement', 'restore', 'data-loss'].includes(spec.id) ? 'at-most' : 'at-least',
  })),
)
