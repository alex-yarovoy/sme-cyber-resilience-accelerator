<script setup lang="ts">
import { zeroTrustPillars } from '../fixtures/zeroTrust'
</script>

<template>
  <h1 class="text-h4 mb-6">Zero Trust</h1>

  <v-row>
    <v-col v-for="pillar in zeroTrustPillars" :key="pillar.id" cols="12" md="6">
      <v-card variant="outlined">
        <v-card-title>{{ pillar.title }}</v-card-title>
        <v-card-subtitle>{{ pillar.summary }}</v-card-subtitle>
        <v-card-text>
          <ul class="pl-4 mb-0">
            <li v-for="control in pillar.controls" :key="control" class="mb-1">
              {{ control }}
            </li>
          </ul>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>
