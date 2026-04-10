package templates

const (
	ValuesTemplate = `---
orchesty:
  nameOverride: orchesty
{{valkeyBlock}}
{{logsBlockOrchesty}}
global:
  orchestyVersion: "{{appOrchestyVersion}}"
  backend_url: https://api-{{instance}}.eu1.cloud.orchesty.io
  frontend_url: https://ui-{{instance}}.eu1.cloud.orchesty.io
  starting_point_url: https://start-{{instance}}.eu1.cloud.orchesty.io
  backend:
    alpha_instance_id: {{instance}}
  metricsCollector:
    enabled: true
  topologyApi:
    topologiesExtraSpec:
      nodeSelector:
        {{bridgePoolKey}}: "true"
      tolerations:
        - effect: NoSchedule
          key: {{bridgePoolKey}}
          operator: Equal
          value: "true"
{{imageOverridesBlock}}
{{resourceLimitsBlock}}
{{logsBlockGlobal}}
{{workersBlock}}
`
	WorkersBlockHeader = `
  workers:
`
	WorkersBlockTemplate = `
    {{workerName}}:
      sdk: {{workerSdkType}}
      image: {{workerImage}}
      {{workerEnvsBlock}}
`
	WorkersEnvsHeader = `
      extraEnv:
`
	WorkersEnvsTemplate = `
        {{key}}:
          value: {{value}}
`
	LogsBlockOrchestyTemplate = `
  grafana:
    enabled: {{grafanaEnabled}}
    service:
      type: LoadBalancer
    persistence:
      storageClassName: standard
    admin:
      existingSecret: orchesty-secrets
  loki:
    enabled: true
    loki:
      limits_config:
        retention_period: {{retentionPeriod}}h
    singleBinary:
      persistence:
        storageClass: standard
        size: {{storageSize}}Gi
      extraEnv:
        - name: S3_ENDPOINT
          valueFrom:
            secretKeyRef:
              name: orchesty-secrets
              key: s3-endpoint
        - name: S3_BUCKET
          valueFrom:
            secretKeyRef:
              name: orchesty-secrets
              key: s3-bucket
        - name: S3_ACCESS_KEY
          valueFrom:
            secretKeyRef:
              name: orchesty-secrets
              key: s3-access-key
        - name: S3_SECRET_KEY
          valueFrom:
            secretKeyRef:
              name: orchesty-secrets
              key: s3-secret-key
  alloy:
    enabled: true
`
	LogsBlockGlobalTemplate = `
  logs:
    lokiHostname: pipes-loki-gateway.{{instance}}.svc.cluster.local
    filter:
      namespaces:
        include: ["{{instance}}"]
        exclude: []
      pods:
        include: ["pipes-worker-.*", "topology-.*"]
        exclude: []
`
	ValkeyBlockTemplate = `
  valkey:
    enabled: {{enabled}}
    dataStorage:
      enabled: {{persistedStorageEnabled}}
      storageClass: standard
      requestedSize: {{persistedStorageSize}}Gi
	{{valkeyCustomResourcesBlock}}
`
	ValkeyCustomResourcesBlockTemplate = `
	resources:
      limits:
          cpu: {{cpuLimit}}m
          memory: {{memoryLimit}}Gi
          ephemeral-storage: {{ephemeralStorageLimit}}Gi
`
	ResourceLimitsBlockTemplate = `
    useQuota: {{limitsEnabled}}
    namespaceQuota:
      resources:
        requests.cpu: {{cpuLimit}}m
        requests.memory: {{memoryLimit}}Mi
        limits.cpu: {{cpuLimit}}m
        limits.memory: {{memoryLimit}}Gi
`
	ImageOverridesBlock = `
    imageOverrides:
      applinth-marketplace-ui: {{hanabosoDockerRegistry}}/{{applinthMarketplaceUiImage}}:{{appOrchestyVersion}}
      backend: {{hanabosoDockerRegistry}}/{{applinthBackendImage}}:{{appOrchestyVersion}}
`
)
