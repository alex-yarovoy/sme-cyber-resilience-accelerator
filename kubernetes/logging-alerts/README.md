# Logging stack on Kubernetes

For many deployments the Elastic stack runs as a managed service. If you colocate collectors on-cluster, run Filebeat or Elastic Agent as a DaemonSet pointed at your cluster log paths; keep Elasticsearch and Kibana on managed infrastructure or the Compose lab in `logging-alerts/`.

See `logging-alerts/README.md` for security hardening and Kibana import steps.
