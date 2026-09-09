import type { ArchitectureDiagram } from './types'

export const diagrams: ArchitectureDiagram[] = [
  {
    id: 'prevent-detect-recover',
    title: 'Prevent, Detect, Recover',
    mermaid: `flowchart LR
  MSP[Northstar Managed]
  Prevent[Prevent]
  Detect[Detect]
  Recover[Recover]
  MSP --> Prevent
  MSP --> Detect
  MSP --> Recover
  Prevent --> Detect
  Detect --> Recover`,
  },
  {
    id: 'zero-trust-access',
    title: 'Zero Trust access path',
    mermaid: `flowchart LR
  User[Staff user] --> IdP[Identity provider]
  IdP --> Device[Device check]
  Device --> Policy[Session policy]
  Policy --> App[Application]
  Policy --> TF[Terraform]
  Policy --> K8s[Kubernetes]`,
  },
  {
    id: 'observability-backup',
    title: 'Observability and backup drill',
    mermaid: `flowchart LR
  Collect[Log collection] --> Cover[Coverage]
  Cover --> Email[Email]
  Cover --> Slack[Slack]
  Cover --> Webhook[Webhook]
  Backup[Backup] --> Drill[Restore drill]
  Drill --> Recover[Recover]`,
  },
]
