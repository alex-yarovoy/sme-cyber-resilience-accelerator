<script setup lang="ts">
import { computed } from 'vue'
import {
  Chart as ChartJS,
  Filler,
  Legend,
  LineElement,
  PointElement,
  RadialLinearScale,
  Tooltip,
  type ChartData,
  type ChartOptions,
} from 'chart.js'
import { Radar } from 'vue-chartjs'
import { navy, teal } from '../theme'
import type { CsfFunction } from '../fixtures/types'

ChartJS.register(Tooltip, Legend, RadialLinearScale, PointElement, LineElement, Filler)

const CSF_KEYS: CsfFunction[] = [
  'govern',
  'identify',
  'protect',
  'detect',
  'respond',
  'recover',
]

const CSF_LABELS = ['Govern', 'Identify', 'Protect', 'Detect', 'Respond', 'Recover']

const props = defineProps<{
  scores: Record<CsfFunction, number>
}>()

const chartData = computed<ChartData<'radar'>>(() => ({
  labels: CSF_LABELS,
  datasets: [
    {
      label: 'CSF 2.0',
      data: CSF_KEYS.map((key) => props.scores[key]),
      backgroundColor: 'rgba(13, 115, 119, 0.2)',
      borderColor: teal,
      pointBackgroundColor: navy,
      pointBorderColor: navy,
    },
  ],
}))

const chartOptions: ChartOptions<'radar'> = {
  responsive: true,
  maintainAspectRatio: true,
  aspectRatio: 1,
  plugins: {
    legend: { display: false },
  },
  scales: {
    r: {
      min: 0,
      max: 100,
      ticks: {
        stepSize: 20,
      },
    },
  },
}
</script>

<template>
  <div class="radar">
    <Radar :data="chartData" :options="chartOptions" aria-label="CSF 2.0 function scores" />
  </div>
</template>

<style scoped>
.radar {
  max-width: 420px;
  margin: 0 auto;
}
</style>
