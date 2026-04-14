package templates

const (
	ChartTemplate = `apiVersion: v2
name: {{chartName}}
description: {{displayName}} Instance
type: application
version: 0.0.1
appVersion: 0.0.1
dependencies:
  - name: orchesty
    version: "{{orchestyVersion}}"
    repository: "https://orchesty.github.io/helm-charts/"
`
)
