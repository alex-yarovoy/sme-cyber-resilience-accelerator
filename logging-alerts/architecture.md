# Architecture

```
[Apps/Services] --(Filebeat/Fluent Bit)--> [Elasticsearch] --> [Kibana]
                         \
                          +--> [Prometheus Node/Blackbox Exporters] --> [Prometheus] --> [Alertmanager] --> [Email/Slack/Webhook]
```

- Structured JSON logs with fields: timestamp, level, service, trace_id, user_id, message, context
- Retention: 14 days warm, 30 days cold (S3/Glacier compatible if cloud)
- PII handling: redact emails, tokens using ingest pipelines


