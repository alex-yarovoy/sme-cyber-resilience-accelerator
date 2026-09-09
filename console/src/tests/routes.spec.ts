import { describe, expect, it } from 'vitest'
import { sampleTenants } from '../fixtures/sampleTenants'
import { router } from '../router'

const namedRoutes = [
  'overview',
  'assessment',
  'controls',
  'rollout',
  'identity',
  'audit',
  'detection',
  'recovery',
  'architecture',
  'zeroTrust',
  'clients',
  'playbooks',
] as const

describe('named routes', () => {
  it.each(namedRoutes)('%s resolves', (name) => {
    expect(router.hasRoute(name)).toBe(true)
    expect(router.resolve({ name }).matched.length).toBeGreaterThan(0)
  })
})

describe('sampleTenants', () => {
  it('ids are cedar-harbor, brightcart, oak-ember, northline', () => {
    expect(sampleTenants.map((tenant) => tenant.id)).toEqual([
      'cedar-harbor',
      'brightcart',
      'oak-ember',
      'northline',
    ])
  })

  it('cedar-harbor MFA and misconfig baseline', () => {
    const cedarHarbor = sampleTenants.find((tenant) => tenant.id === 'cedar-harbor')
    expect(cedarHarbor).toBeDefined()
    expect(cedarHarbor?.mfaPercent).toBe(96)
    expect(cedarHarbor?.misconfigsBaseline).toBe(25)
  })
})
