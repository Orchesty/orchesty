{{- define "pipes.monolithEnv" -}}
- name: BACKEND_DSN
  value: "{{ .Values.global.backend_url }}/"
- name: FRONTEND_DSN
  value: "{{ .Values.global.frontend_url }}/"
- name: NOTIFICATION_DSN
  value: notification-sender-api
- name: MONOLITH_API_DSN
  value: worker
- name: __MONGODB_AUTH_AND_HOSTS
  valueFrom:
    configMapKeyRef:
      key: mongodb_auth_and_hosts
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: MONGODB_DSN
  valueFrom:
    configMapKeyRef:
      key: mongodb_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: MONGODB_DB
  valueFrom:
    configMapKeyRef:
      key: mongodb_database
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: MONGODB_NOTIFICATION_DATABASE
  valueFrom:
    configMapKeyRef:
      key: mongodb_database
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: ELASTIC_HOST
  value: elasticsearch
- name: ELASTIC_INDEX
  value: logstash
- name: EMAIL_DSN
  valueFrom:
    configMapKeyRef:
      key: email_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: METRICS_HOST
{{- if .Values.global.monitoring.useInfluxDB }}
  value: kapacitor
{{- else }}
  value: "$(__MONGODB_AUTH_AND_HOSTS)"
{{- end }}
- name: METRICS_PORT
  value: "{{ if .Values.global.monitoring.useInfluxDB }}9100{{ else }}27017{{ end }}"
- name: METRICS_SERVICE
  value: {{ if .Values.global.monitoring.useInfluxDB }}influx{{ else }}mongo{{ end }}

## todo: sdk needs this, investigate
- name: BACKEND_HOST
  value: "{{ .Values.global.backend_url }}/"
- name: RABBITMQ_DSN
  valueFrom:
    configMapKeyRef:
      key: rabbitmq_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: METRICS_DB
  valueFrom:
    configMapKeyRef:
      key: metrics_db
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: METRICS_DSN
{{- if .Values.global.monitoring.useInfluxDB }}
  value: influxdb://kapacitor:9100
{{- else }}
  value: mongodb://$(__MONGODB_AUTH_AND_HOSTS)/$(METRICS_DB)?readPreference=secondaryPreferred
{{- end }}
#####

- name: RABBIT_DSN
  valueFrom:
    configMapKeyRef:
      key: rabbitmq_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: ELASTICSEARCH_DSN
  valueFrom:
    configMapKeyRef:
      key: elasticsearch_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: MULTI_PROBE_DSN
  value: multi-probe:8007
- name: STARTING_POINT_DSN
  value: "{{ .Values.global.starting_point_url }}"
- name: TOPOLOGY_API_DSN
  value: topology-api:8080

{{- if not .Values.global.imageOverrides.bridge }}
- name: DOCKER_REGISTRY
  value: {{ .Values.global.imageRegistry.server }}/{{ .Values.global.imageRegistry.path }}
- name: DOCKER_PF_BRIDGE_IMAGE
  value: {{ .Values.global.images.bridge }}
{{- else }}
- name: DOCKER_REGISTRY
  value: ""
- name: DOCKER_PF_BRIDGE_IMAGE
  value: {{ .Values.global.imageOverrides.bridge }}
{{- end }}

- name: XML_PARSER_API_DSN
  value: xml-parser-api
- name: CRON_DSN
  value: cron-api:8080
- name: PHP_FPM_MAX_REQUESTS
  value: "5000"
- name: PHP_FPM_MAX_CHILDREN
  value: "20"
- name: WORKER_DEFAULT_PORT
  value: "8000"
{{- end -}}
