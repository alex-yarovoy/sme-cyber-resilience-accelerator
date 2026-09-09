import type { ZeroTrustPillar } from './types'

export const zeroTrustPillars: ZeroTrustPillar[] = [
  {
    id: 'identity',
    title: 'Identity',
    summary: 'Staff sign-in is the control plane for SaaS and admin paths.',
    controls: [
      'MFA on every staff identity-provider account',
      'Passkeys for owners, finance, and privileged operators',
      'No shared admin mailboxes',
      'Identity provider as the source of truth for joiner, mover, and leaver',
    ],
  },
  {
    id: 'device',
    title: 'Device',
    summary: 'Staff laptops meet a small, enforceable baseline before access.',
    controls: [
      'Disk encryption on staff workstations',
      'OS update SLA for laptops that reach production systems',
      'No unmanaged admin workstations',
      'Separate guest devices from staff access',
    ],
  },
  {
    id: 'network-session',
    title: 'Network and session',
    summary: 'Sessions are short-lived and scoped to the application, not the LAN.',
    controls: [
      'SSO into SaaS instead of local passwords',
      'Session timeout on privileged consoles',
      'Staff Wi-Fi isolated from guest Wi-Fi',
      'Least-privilege path for admin work, not flat VPN',
    ],
  },
  {
    id: 'data',
    title: 'Data',
    summary: 'Secrets, backups, and customer records stay in named stores with review.',
    controls: [
      'KMS-backed secrets; no long-lived keys in tickets or chat',
      'Encrypted, versioned backups with a tested restore',
      'Guest and customer PII limited to systems that need it',
      'Quarterly access review for finance and booking or storefront roles',
    ],
  },
]
