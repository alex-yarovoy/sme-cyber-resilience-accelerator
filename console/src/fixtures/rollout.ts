import type { RolloutPhase } from './types'

export const rolloutPhases: RolloutPhase[] = [
  {
    id: 'assess',
    label: 'Assess',
    dayStart: 0,
    dayEnd: 30,
    focus: 'Baseline identity, logging, backup, and misconfiguration posture',
  },
  {
    id: 'kits',
    label: 'Kits',
    dayStart: 30,
    dayEnd: 60,
    focus: 'Deploy identity, logging, and recovery kits against program targets',
  },
  {
    id: 'drill',
    label: 'Drill and measure',
    dayStart: 60,
    dayEnd: 90,
    focus: 'Run the restore drill and measure MTTD, MTTR, RTO, and RPO',
  },
]
