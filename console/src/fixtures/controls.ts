import { sampleTenants } from './sampleTenants'
import type { ControlRow, CsfFunction, SampleTenant } from './types'

interface ControlSpec {
  id: string
  policyTheme: string
  control: string
  kpiLabel: string
  target: number
  unit: string
  csf: CsfFunction
  currentOf: (tenant: SampleTenant) => number
}

const specs: ControlSpec[] = [
  {
    id: 'mfa',
    policyTheme: 'Identity-first Zero Trust',
    control: 'Staff MFA on the identity provider',
    kpiLabel: 'MFA coverage',
    target: 95,
    unit: '%',
    csf: 'protect',
    currentOf: (tenant) => tenant.mfaPercent,
  },
  {
    id: 'kms',
    policyTheme: 'Encryption',
    control: 'Secrets stored in KMS',
    kpiLabel: 'KMS-backed secrets',
    target: 100,
    unit: '%',
    csf: 'protect',
    currentOf: (tenant) => tenant.kmsSecretsPercent,
  },
  {
    id: 'logging',
    policyTheme: 'Logging',
    control: 'Central log coverage',
    kpiLabel: 'Log coverage',
    target: 90,
    unit: '%',
    csf: 'detect',
    currentOf: (tenant) => tenant.logCoveragePercent,
  },
  {
    id: 'mttd',
    policyTheme: 'Detection',
    control: 'Mean time to detect',
    kpiLabel: 'MTTD',
    target: 7,
    unit: 'h',
    csf: 'detect',
    currentOf: (tenant) => tenant.mttdHours,
  },
  {
    id: 'mttr',
    policyTheme: 'Response',
    control: 'Mean time to respond',
    kpiLabel: 'MTTR',
    target: 8,
    unit: 'h',
    csf: 'respond',
    currentOf: (tenant) => tenant.mttrHours,
  },
  {
    id: 'rto',
    policyTheme: 'Recovery',
    control: 'Recovery time objective',
    kpiLabel: 'RTO',
    target: 4,
    unit: 'h',
    csf: 'recover',
    currentOf: (tenant) => tenant.rtoHours,
  },
  {
    id: 'rpo',
    policyTheme: 'Recovery',
    control: 'Recovery point objective',
    kpiLabel: 'RPO',
    target: 1,
    unit: 'h',
    csf: 'recover',
    currentOf: (tenant) => tenant.rpoHours,
  },
  {
    id: 'misconfigs',
    policyTheme: 'Configuration',
    control: 'Cloud misconfiguration reduction',
    kpiLabel: 'Open misconfigs',
    target: 10,
    unit: 'count',
    csf: 'identify',
    currentOf: (tenant) => tenant.misconfigsCurrent,
  },
]

export const controls: ControlRow[] = sampleTenants.flatMap((tenant) =>
  specs.map((spec) => ({
    id: `${tenant.id}-${spec.id}`,
    tenantId: tenant.id,
    policyTheme: spec.policyTheme,
    control: spec.control,
    kpiLabel: spec.kpiLabel,
    current: spec.currentOf(tenant),
    target: spec.target,
    unit: spec.unit,
    csf: spec.csf,
  })),
)
