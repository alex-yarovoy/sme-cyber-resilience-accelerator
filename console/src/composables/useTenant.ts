import { computed, ref } from 'vue'
import { sampleTenants } from '../fixtures/sampleTenants'
import type { SampleTenant } from '../fixtures/types'

const selectedId = ref(sampleTenants[0].id)

export function useTenant() {
  const tenant = computed(
    (): SampleTenant =>
      sampleTenants.find((item) => item.id === selectedId.value) ?? sampleTenants[0],
  )

  return {
    tenant,
    tenants: sampleTenants,
    selectedId,
  }
}
