{{/* vim: set filetype=mustache: */}}

{{- define "pipes.imagePullSecrets" }}
{{- if .Values.global.imageRegistry.enablePullSecret -}}
imagePullSecrets:
  - name: {{ include "pipes.fullname" (dict "root" . "suffix" "pull-secret") }}
{{- end -}}
{{- end -}}

{{- define "pipes.imageFullname" -}}
{{- $defaultImage := get .root.Values.global.images .image -}}
{{- $version := .root.Values.global.orchestyVersion -}}
{{- $defaultImageFull := printf "%s/%s/%s:%s" .root.Values.global.imageRegistry.server .root.Values.global.imageRegistry.path $defaultImage $version -}}
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
