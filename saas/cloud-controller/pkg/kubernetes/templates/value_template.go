package templates

const (
	ValuesTemplate = `---
orchesty:
  nameOverride: orchesty
{{valkeyBlock}}
{{logsBlockOrchesty}}
global:
  orchestyVersion: "{{appOrchestyVersion}}"
  backend_url: https://api-{{instancePrefix}}-{{instance}}.{{domainSuffix}}
  frontend_url: https://ui-{{instancePrefix}}-{{instance}}.{{domainSuffix}}
  starting_point_url: https://start-{{instancePrefix}}-{{instance}}.{{domainSuffix}}
  backend:
    alpha_instance_id: {{instance}}
    postInstall:
      createDefaultUser: false
  frontend:
    title: "{{frontendTitle}}"
  metricsCollector:
    enabled: true
  topologyApi:
    topologiesExtraEnv:
      BACKEND_URL:
        value: https://api-{{instancePrefix}}-{{instance}}.{{domainSuffix}}
      LIMITS_CHECK_INTERVAL:
        value: "60"
    topologiesExtraSpec:
      nodeSelector:
        {{bridgePoolKey}}: "true"
      tolerations:
        - effect: NoSchedule
          key: {{bridgePoolKey}}
          operator: Equal
          value: "true"
  cloud:
    enabled: true
    backendUrl: https://api.cloud.orchesty.io
    frontendUrl: https://app.cloud.orchesty.io
    startingPointUrl: https://start-{{cloudInstancePrefix}}-{{cloudInstance}}.{{domainSuffix}}
    instance:
      notifierUrl: https://ses-{{instancePrefix}}-{{instance}}.{{domainSuffix}}
      traceUrl: https://ws-{{instancePrefix}}-{{instance}}.{{domainSuffix}}
      tunnelProxyUrl: https://proxy-{{instancePrefix}}-{{instance}}.{{domainSuffix}}
    features:
      enterpriseDashboards: '{{featureEnterpriseDashboards}}'
      traceAuditing: '{{featureTraceAuditing}}'
      auditLogs: '{{featureAuditLogs}}'
      pulse: '{{featurePulse}}'
    limits:
      topologySlots: '{{limitTopologySlots}}'
      messages: '{{limitMessages}}'
      storageGb: '{{limitStorageGb}}'
      trashDuplication: '{{limitTrashDuplication}}'
    auth0:
      domain: {{auth0Domain}}
      audience: {{auth0Audience}}
      clientId: {{auth0ClientId}}
  notifier:
    startingPointDsn: https://start-{{cloudInstancePrefix}}-{{cloudInstance}}.{{domainSuffix}}
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
      limits.memory: {{memoryLimit}}Mi
`
	ImageOverridesBlockHeaderTemplate = `
  imageOverrides:
  {{imageOverridesBlock}}
`
	ImageOverridesApplinthBlockTemplate = `
    applinth-marketplace-ui: {{hanabosoDockerRegistry}}/{{applinthMarketplaceUiImage}}:{{appOrchestyVersion}}
    backend: {{hanabosoDockerRegistry}}/{{applinthBackendImage}}:{{appOrchestyVersion}}
`
	ImageOverridesBlockTemplate = `
    frontend: {{hanabosoDockerRegistry}}/{{enterpriseFrontendImage}}:{{appOrchestyVersion}}
    backend: {{hanabosoDockerRegistry}}/{{enterpriseBackendImage}}:{{appOrchestyVersion}}
    tunnel-proxy: {{hanabosoDockerRegistry}}/{{tunnelProxyImage}}:{{appOrchestyVersion}}
    trace: {{hanabosoDockerRegistry}}/{{traceImage}}:{{appOrchestyVersion}}
    notifier: {{hanabosoDockerRegistry}}/{{notifierImage}}:{{appOrchestyVersion}}
    metrics-collector: {{hanabosoDockerRegistry}}/{{metricsCollectorImage}}:{{appOrchestyVersion}}
`
)
