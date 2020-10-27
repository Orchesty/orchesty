{{/* vim: set filetype=mustache: */}}

{{- define "pipes.imagePullSecrets" }}
{{- if .Values.global.imageRegistry.enablePullSecret -}}
imagePullSecrets:
  - name: {{ include "pipes.fullname" (dict "root" . "suffix" "pull-secret") }}
{{- end -}}
{{- end -}}

{{- define "pipes.imageFullname" -}}
{{- $defaultImage := get .root.Values.global.images .image -}}
{{- $defaultImageFull := printf "%s/%s/%s" .root.Values.global.imageRegistry.server .root.Values.global.imageRegistry.path $defaultImage -}}
{{- default $defaultImageFull (get .root.Values.global.imageOverrides .image) -}}
{{- end -}}

{{/* common labels */}}

{{- define "pipes.labels" -}}
helm.sh/chart: {{ include "pipes.chart" . }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end -}}

{{/* frontend templates */}}

{{- define "pipes.selectorLabels" -}}
app.kubernetes.io/name: {{ include "pipes.name" .root }}-{{ .suffix }}
app.kubernetes.io/instance: {{ .root.Release.Name }}
{{- end -}}

{{- define "pipes.dictSection" -}}
{{- if .items -}}
{{- if .name -}}
{{ $.name }}:
{{- end -}}
{{- range $key, $value := .items }}
{{ if $.name }}  {{ end }}{{ $key }}: {{ $value | quote}}
{{- end -}}
{{- end -}}
{{- end -}}

{{/*
Expand the name of the chart.
*/}}
{{- define "pipes.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{/*
Create a default fully qualified app name.
We truncate at 63 chars because some Kubernetes name fields are limited to this (by the DNS naming spec).
If release name contains chart name it will be used as a full name.
*/}}
{{- define "pipes.fullname" -}}
{{- if not .root -}}
{{- $_ := set . "root" . -}}
{{- end -}}
{{- if not .noPrefix -}}
{{- if .root.Values.fullnameOverride -}}
{{- .root.Values.fullnameOverride | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- $name := default .root.Chart.Name .root.Values.nameOverride -}}
{{- if contains $name .root.Release.Name -}}
{{- .root.Release.Name | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- printf "%s-%s" .root.Release.Name $name | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}
{{- end -}}
{{- if .suffix -}}
{{- if not .noPrefix -}}-{{- end -}}{{- .suffix -}}
{{- end -}}
{{- end -}}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "pipes.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{/*
Create the name of the service account to use
*/}}
{{- define "pipes.serviceAccountName" -}}
{{- if .Values.serviceAccount.create -}}
    {{ default (include "pipes.fullname" .) .Values.serviceAccount.name }}
{{- else -}}
    {{ default "default" .Values.serviceAccount.name }}
{{- end -}}
{{- end -}}

{{/*
TODO: move to _monolith-env.tpl
*/}}
{{- define "pipes.monolithEnv" -}}
- name: BACKEND_HOST
  value: "{{ .Values.global.backend_url }}/"
- name: FRONTEND_HOST
  value: "{{ .Values.global.frontend_url }}/"
- name: MONOLITH_API_HOST
  value: monolith-api
- name: MONOLITH_API_DSN
  value: monolith-api
- name: MONGODB_DSN
  valueFrom:
    configMapKeyRef:
      key: mongodb_dsn
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
- name: MONGODB_DATABASE
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
  valueFrom:
    configMapKeyRef:
      key: mongodb_auth_and_hosts
      name: {{ include "pipes.fullname" (dict "suffix" "secrets" "root" .) }}
{{- end }}
- name: METRICS_PORT
  value: "{{ if .Values.global.monitoring.useInfluxDB }}9100{{ else }}27017{{ end }}"
- name: METRICS_SERVICE
  value: {{ if .Values.global.monitoring.useInfluxDB }}influx{{ else }}mongo{{ end }}
- name: RABBITMQ_DSN
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
  value: starting-point:8080
- name: TOPOLOGY_API_DSN
  value: topology-api:8080
- name: DOCKER_REGISTRY
  value: {{ .Values.global.imageRegistry.server }}/{{ .Values.global.imageRegistry.path }}
- name: DOCKER_PF_BRIDGE_IMAGE
  value: {{ .Values.global.images.bridge }}
- name: XML_PARSER_API_DSN
  value: xml-parser-api
- name: CRON_DSN
  value: cron-api:8080  
- name: PHP_FPM_MAX_REQUESTS
  value: "5000"
- name: PHP_FPM_MAX_CHILDREN
  value: "20"
- name: USER_TASK_LISTENER_ENABLE
  value: "{{ .Values.global.monolith.userTaskListenerEnable }}"
{{ if .Values.global.monolith.extraEnv -}}
{{ toYaml .Values.global.monolith.extraEnv }}
{{- end -}}
{{- end -}}
