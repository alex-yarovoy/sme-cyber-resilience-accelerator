<script setup lang="ts">
defineProps<{
  label: string
  value: string
}>()
</script>

<template>
  <v-card variant="outlined">
    <v-card-text>
      <div class="text-caption text-medium-emphasis">{{ label }}</div>
      <div class="text-h5 font-weight-medium mt-1">{{ value }}</div>
    </v-card-text>
  </v-card>
</template>
