<script setup lang="ts">
import { useTenant } from '../composables/useTenant'

const { tenants, selectedId } = useTenant()

const groups = [
  {
    title: 'Program',
    items: [
      { title: 'Overview', to: { name: 'overview' } },
      { title: 'Assessment', to: { name: 'assessment' } },
      { title: 'Controls', to: { name: 'controls' } },
      { title: 'Rollout', to: { name: 'rollout' } },
    ],
  },
  {
    title: 'Operations',
    items: [
      { title: 'Identity', to: { name: 'identity' } },
      { title: 'Audit', to: { name: 'audit' } },
      { title: 'Detection', to: { name: 'detection' } },
      { title: 'Recovery', to: { name: 'recovery' } },
    ],
  },
  {
    title: 'Model',
    items: [
      { title: 'Architecture', to: { name: 'architecture' } },
      { title: 'Zero Trust', to: { name: 'zeroTrust' } },
      { title: 'Playbooks', to: { name: 'playbooks' } },
    ],
  },
  {
    title: 'MSP',
    items: [
      { title: 'Clients', to: { name: 'clients' } },
    ],
  },
]
</script>

<template>
  <v-app>
    <v-navigation-drawer permanent width="260">
      <div class="px-4 py-4">
        <div class="text-subtitle-1 font-weight-medium">Operator console</div>
      </div>
      <v-divider />
      <v-list density="compact" nav>
        <template v-for="group in groups" :key="group.title">
          <v-list-subheader>{{ group.title }}</v-list-subheader>
          <v-list-item
            v-for="item in group.items"
            :key="item.title"
            :to="item.to"
            :title="item.title"
            color="primary"
          />
        </template>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar color="primary" flat>
      <v-app-bar-title>SME Cyber Resilience Accelerator</v-app-bar-title>
      <v-select
        v-model="selectedId"
        :items="tenants"
        item-title="name"
        item-value="id"
        hide-details
        density="compact"
        variant="solo"
        class="tenant-select"
      />
      <v-chip class="mx-4" color="secondary" variant="flat">Sample tenant</v-chip>
    </v-app-bar>

    <v-main>
      <v-container class="py-6" fluid>
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.tenant-select {
  max-width: 240px;
}
</style>
