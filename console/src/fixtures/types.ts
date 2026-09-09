export type Sector = 'hospitality' | 'ecommerce'
export type CsfFunction = 'govern' | 'identify' | 'protect' | 'detect' | 'respond' | 'recover'

export interface SampleTenant {
  id: string
  name: string
  sector: Sector
  msp: 'Northstar Managed'
  rolloutDay: number
  csf: Record<CsfFunction, number>
  mfaPercent: number
  passkeyPercent: number
  misconfigsCurrent: number
  misconfigsTarget: number
  misconfigsBaseline: number
  logCoveragePercent: number
  mttdHours: number
  mttrHours: number
  rtoHours: number
  rpoHours: number
  lastDrillAt: string
  lastDrillHours: number
  lastDrillPassed: boolean
  kmsSecretsPercent: number
  assetCount: number
}
