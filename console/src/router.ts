import { createRouter, createWebHistory } from 'vue-router'
import OverviewView from './views/OverviewView.vue'
import AssessmentView from './views/AssessmentView.vue'
import ControlsView from './views/ControlsView.vue'
import RolloutView from './views/RolloutView.vue'
import IdentityView from './views/IdentityView.vue'
import AuditView from './views/AuditView.vue'
import DetectionView from './views/DetectionView.vue'
import RecoveryView from './views/RecoveryView.vue'
import ArchitectureView from './views/ArchitectureView.vue'
import ZeroTrustView from './views/ZeroTrustView.vue'
import ClientsView from './views/ClientsView.vue'
import PlaybooksView from './views/PlaybooksView.vue'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'overview', component: OverviewView },
    { path: '/assessment', name: 'assessment', component: AssessmentView },
    { path: '/controls', name: 'controls', component: ControlsView },
    { path: '/rollout', name: 'rollout', component: RolloutView },
    { path: '/identity', name: 'identity', component: IdentityView },
    { path: '/audit', name: 'audit', component: AuditView },
    { path: '/detection', name: 'detection', component: DetectionView },
    { path: '/recovery', name: 'recovery', component: RecoveryView },
    { path: '/architecture', name: 'architecture', component: ArchitectureView },
    { path: '/zero-trust', name: 'zeroTrust', component: ZeroTrustView },
    { path: '/clients', name: 'clients', component: ClientsView },
    { path: '/playbooks', name: 'playbooks', component: PlaybooksView },
  ],
})
