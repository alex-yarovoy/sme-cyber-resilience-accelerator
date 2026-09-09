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

export interface ControlRow {
  id: string
  tenantId: string
  policyTheme: string
  control: string
  kpiLabel: string
  current: number
  target: number
  unit: string
  csf: CsfFunction
}

export type AuditEventType = 'login' | 'mfa' | 'passkey' | 'policy-change'
export type AuditOutcome = 'success' | 'failure'

export interface AuditEvent {
  id: string
  tenantId: string
  type: AuditEventType
  ip: string
  outcome: AuditOutcome
  at: string
}

export type AlertChannel = 'Email' | 'Slack' | 'Webhook'
export type AlertRouteStatus = 'active' | 'degraded' | 'unconfigured'

export interface AlertRoute {
  channel: AlertChannel
  status: AlertRouteStatus
}

export interface DetectionSnapshot {
  tenantId: string
  logCoveragePercent: number
  alertRoutes: AlertRoute[]
  failedLoginCount: number
}

export interface RolloutPhase {
  id: string
  label: string
  dayStart: number
  dayEnd: number
  focus: string
}

export interface ArchitectureDiagram {
  id: string
  title: string
  mermaid: string
}

export type ZeroTrustPillarId = 'identity' | 'device' | 'network-session' | 'data'

export interface ZeroTrustPillar {
  id: ZeroTrustPillarId
  title: string
  summary: string
  controls: string[]
}

export interface PlaybookItem {
  id: string
  title: string
  surfaces: string[]
  steps: string[]
}

export interface SectorPlaybook {
  sector: Sector
  productLabel: string
  items: PlaybookItem[]
}
