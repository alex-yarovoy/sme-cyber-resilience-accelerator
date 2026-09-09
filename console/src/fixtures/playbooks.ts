import type { SectorPlaybook } from './types'

export const playbooks: SectorPlaybook[] = [
  {
    sector: 'hospitality',
    productLabel: 'booking SaaS',
    items: [
      {
        id: 'hosp-pms',
        title: 'PMS unavailable',
        surfaces: ['PMS', 'bookings'],
        steps: [
          'Fail bookings to the booking SaaS waitlist and freeze rate updates',
          'Confirm last good backup and RPO before any restore',
          'Restore PMS to the last known-good snapshot inside RTO',
          'Reconcile in-house folios against the booking SaaS after restore',
        ],
      },
      {
        id: 'hosp-bookings',
        title: 'Booking SaaS account takeover',
        surfaces: ['bookings', 'guest PII'],
        steps: [
          'Revoke sessions on the booking SaaS and rotate operator credentials',
          'Require MFA or passkey before operators return',
          'Review rate, inventory, and guest-message changes since last good login',
          'Notify affected guests if reservation or PII records were changed',
        ],
      },
      {
        id: 'hosp-pii',
        title: 'Guest PII exposure',
        surfaces: ['guest PII', 'PMS'],
        steps: [
          'Contain the export path and disable the implicated integration',
          'Inventory guest records in the exposure window',
          'Preserve logs for the audit trail',
          'Issue required guest notice and close the export path',
        ],
      },
    ],
  },
  {
    sector: 'ecommerce',
    productLabel: 'storefront',
    items: [
      {
        id: 'ecom-checkout',
        title: 'Checkout interruption',
        surfaces: ['checkout', 'storefront'],
        steps: [
          'Take the storefront to a read-only catalog if payments cannot complete',
          'Preserve cart and order logs for the interruption window',
          'Fail closed on the payments-adjacent connector until health returns',
          'Replay or cancel in-flight orders after the storefront is stable',
        ],
      },
      {
        id: 'ecom-payments',
        title: 'Payments-adjacent processor timeout',
        surfaces: ['checkout', 'payments-adjacent'],
        steps: [
          'Stop new captures; allow authorized-but-unsettled orders to queue',
          'Confirm processor status independently of the storefront admin',
          'Reconcile authorizations against the order log before retry',
          'Resume capture only after duplicate-charge checks pass',
        ],
      },
      {
        id: 'ecom-pii',
        title: 'Customer PII exposure',
        surfaces: ['PII', 'storefront'],
        steps: [
          'Disable the implicated export, app, or webhook',
          'Inventory customer records in the exposure window',
          'Rotate storefront API keys and session secrets',
          'Issue required customer notice and close the export path',
        ],
      },
    ],
  },
]
