{{- define "pipes.monolithEnv" -}}
- name: BACKEND_DSN
  value: "{{ .Values.global.backend_url }}/"
- name: FRONTEND_DSN
  value: "{{ .Values.global.frontend_url }}/"
- name: MONOLITH_API_DSN
  value: worker
- name: MONGODB_DSN
  valueFrom:
    secretKeyRef:
      key: mongodb_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: MONGODB_DB
  valueFrom:
    secretKeyRef:
      key: mongodb_db
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: MONGODB_NOTIFICATION_DATABASE
  valueFrom:
    secretKeyRef:
      key: mongodb_db
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: EMAIL_DSN
  valueFrom:
    secretKeyRef:
      key: email_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: METRICS_SERVICE
  value: mongo

## todo: sdk needs this, investigate
- name: BACKEND_HOST
  value: "{{ .Values.global.backend_url }}/"
- name: RABBITMQ_DSN
  valueFrom:
    secretKeyRef:
      key: rabbitmq_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: METRICS_DB
  valueFrom:
    secretKeyRef:
      key: metrics_db
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: METRICS_DSN
  valueFrom:
    secretKeyRef:
      key: metrics_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}

#####

- name: RABBIT_DSN
  valueFrom:
    secretKeyRef:
      key: rabbitmq_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
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
