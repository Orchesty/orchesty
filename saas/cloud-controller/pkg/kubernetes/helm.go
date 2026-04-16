package kubernetes

import (
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/kubernetes/templates"
	"cloud-controller/pkg/models"
)

const (
	helmBinary      = "helm"
	orchestyRepo    = "orchesty"
	orchestyRepoURL = "https://orchesty.github.io/helm-charts/"
)

var recommendedNameRegex = regexp.MustCompile(`^(([A-Za-z0-9][-A-Za-z0-9_.]*)?[A-Za-z0-9])?$`)

type Helm struct {
	executeFn func(args ...string) (string, error)
}

func NewHelm() *Helm {
	return &Helm{}
}

func (h *Helm) Install(dto *models.InstanceDTO) error {
	chartPath := filepath.Join(config.Helm.RootDirForFiles, dto.Instance)

	if err := os.MkdirAll(chartPath, 0o755); err != nil {
		return err
	}

	if err := h.createFiles(chartPath, dto); err != nil {
		return fmt.Errorf("failed to create helm files at %s: %w", chartPath, err)
	}

	if err := h.dependency(chartPath); err != nil {
		return fmt.Errorf("%w (helm files kept at %s)", err, chartPath)
	}

	installArgs := []string{
		"upgrade",
		"-i",
		"orchesty",
		"-f",
		filepath.Join(chartPath, "Values.yaml"),
		chartPath,
		"--namespace",
		dto.Instance,
	}

	kubeConfigArgs := h.getKubeConfigArgs()
	installArgs = append(installArgs, kubeConfigArgs...)

	if output, err := h.execute(installArgs...); err != nil {
		return fmt.Errorf("helm install failed: %w, output: %s (helm files kept at %s)", err, output, chartPath)
	}

	if err := os.RemoveAll(chartPath); err != nil {
		config.Logger.Error(fmt.Errorf("failed to cleanup helm files at %s: %w", chartPath, err))
	}

	return nil
}

func (h *Helm) dependency(path string) error {
	output, err := h.execute("repo", "add", orchestyRepo, orchestyRepoURL, "--force-update")
	if err != nil {
		return fmt.Errorf("helm repo add failed: %w, output: %s", err, output)
	}

	output, err = h.execute("repo", "update", orchestyRepo)
	if err != nil {
		return fmt.Errorf("helm repo update failed: %w, output: %s", err, output)
	}

	output, err = h.execute("dependency", "build", path)
	if err != nil {
		return fmt.Errorf("helm dependency build failed: %w, output: %s", err, output)
	}

	return nil
}

func (h *Helm) execute(args ...string) (string, error) {
	if h.executeFn != nil {
		return h.executeFn(args...)
	}

	command := exec.Command(helmBinary, args...)
	out, err := command.CombinedOutput()

	output := strings.TrimSpace(string(out))
	if output != "" {
		config.Logger.Info(output, map[string]interface{}{})
	}

	if err != nil {
		config.Logger.Error(err)
	}

	return output, err
}

func (h *Helm) createFiles(path string, dto *models.InstanceDTO) error {
	sanitizedChartName, err := sanitizeK8sName(dto.InstanceDisplayName)
	if err != nil {
		return fmt.Errorf("invalid instance display name for chart name: %w", err)
	}

	chart := strings.ReplaceAll(templates.ChartTemplate, "{{chartName}}", sanitizedChartName)
	chart = strings.ReplaceAll(chart, "{{displayName}}", dto.InstanceDisplayName)
	chart = strings.ReplaceAll(chart, "{{orchestyVersion}}", config.Helm.OrchestyVersion)

	if err := os.WriteFile(filepath.Join(path, "Chart.yaml"), []byte(chart), 0o600); err != nil {
		return err
	}

	values := strings.ReplaceAll(templates.ValuesTemplate, "{{valkeyBlock}}", h.buildValkey(dto))
	values = strings.ReplaceAll(values, "{{logsBlockOrchesty}}", h.buildLogs(dto, false))
	values = strings.ReplaceAll(values, "{{imageOverridesBlock}}", h.buildImageOverrides(dto))
	values = strings.ReplaceAll(values, "{{resourceLimitsBlock}}", h.buildResourceLimits(dto))
	values = strings.ReplaceAll(values, "{{logsBlockGlobal}}", h.buildLogs(dto, true))
	values = strings.ReplaceAll(values, "{{workersBlock}}", h.buildWorkers(dto))

	// Instance prefix and instance replacement in public URLs
	values = strings.ReplaceAll(values, "{{instancePrefix}}", dto.InstanceUrlPrefix)
	values = strings.ReplaceAll(values, "{{instance}}", dto.Instance)

	// Cloud instance prefix and cloud instance replacement in public URLs
	values = strings.ReplaceAll(values, "{{cloudInstancePrefix}}", config.Cloud.InstancePrefix)
	values = strings.ReplaceAll(values, "{{cloudInstance}}", config.Cloud.Instance)

	// Feature flags replacement
	values = strings.ReplaceAll(values, "{{featureEnterpriseDashboards}}", strconv.FormatBool(dto.Customizations.Features.EnterpriseDashboards))
	values = strings.ReplaceAll(values, "{{featureTraceAuditing}}", strconv.FormatBool(dto.Customizations.Features.TraceAuditing))
	values = strings.ReplaceAll(values, "{{featureAuditLogs}}", strconv.FormatBool(dto.Customizations.Features.AuditLogs))
	values = strings.ReplaceAll(values, "{{featurePulse}}", strconv.FormatBool(dto.Customizations.Features.Pulse))

	// Instance limits replacement
	values = strings.ReplaceAll(values, "{{limitTopologySlots}}", strconv.Itoa(dto.Customizations.ResourceLimits.TopologySlots))
	values = strings.ReplaceAll(values, "{{limitMessages}}", strconv.Itoa(dto.Customizations.ResourceLimits.Messages))
	values = strings.ReplaceAll(values, "{{limitStorageGb}}", strconv.Itoa(dto.Customizations.ResourceLimits.StorageGb))
	values = strings.ReplaceAll(values, "{{limitTrashDuplication}}", strconv.Itoa(dto.Customizations.ResourceLimits.TrashDuplication))

	// Auth0 configuration replacement
	values = strings.ReplaceAll(values, "{{auth0Domain}}", config.Cloud.Oauth0Domain)
	values = strings.ReplaceAll(values, "{{auth0Audience}}", config.Cloud.Oauth0Audience)
	values = strings.ReplaceAll(values, "{{auth0ClientId}}", config.Cloud.Oauth0ClientId)

	// Other replacements
	values = strings.ReplaceAll(values, "{{frontendTitle}}", dto.InstanceDisplayName)
	values = strings.ReplaceAll(values, "{{appOrchestyVersion}}", config.Orchesty.Version)
	values = strings.ReplaceAll(values, "{{domainSuffix}}", config.Cloud.DomainSuffix)
	values = strings.ReplaceAll(values, "{{bridgePoolKey}}", config.Helm.BridgePoolKey)

	return os.WriteFile(filepath.Join(path, "Values.yaml"), []byte(values), 0o600)
}

func (h *Helm) buildValkey(dto *models.InstanceDTO) string {
	if !dto.Customizations.Valkey.Enabled {
		return ""
	}

	valkeyBlock := strings.ReplaceAll(templates.ValkeyBlockTemplate, "{{enabled}}", strconv.FormatBool(dto.Customizations.Valkey.Enabled))
	valkeyBlock = strings.ReplaceAll(valkeyBlock, "{{persistedStorageEnabled}}", strconv.FormatBool(dto.Customizations.Valkey.PersistentStorage.Enabled))
	valkeyBlock = strings.ReplaceAll(valkeyBlock, "{{persistedStorageSize}}", strconv.Itoa(dto.Customizations.Valkey.PersistentStorage.Size))

	customResourcesBlock := ""
	if dto.Customizations.Valkey.Limit != (models.ValkeyLimit{}) {
		customResourcesBlock = strings.ReplaceAll(templates.ValkeyCustomResourcesBlockTemplate, "{{cpuLimit}}", strconv.Itoa(dto.Customizations.Valkey.Limit.CPU))
		customResourcesBlock = strings.ReplaceAll(customResourcesBlock, "{{memoryLimit}}", strconv.Itoa(dto.Customizations.Valkey.Limit.Memory))
		customResourcesBlock = strings.ReplaceAll(customResourcesBlock, "{{ephemeralStorageLimit}}", strconv.Itoa(dto.Customizations.Valkey.Limit.Storage))
	}
	valkeyBlock = strings.ReplaceAll(valkeyBlock, "{{valkeyCustomResourcesBlock}}", customResourcesBlock)

	return valkeyBlock
}

func (h *Helm) buildLogs(dto *models.InstanceDTO, global bool) string {
	if !dto.Customizations.Logs.Enabled {
		return ""
	}

	if global {
		globalLogs := strings.ReplaceAll(templates.LogsBlockGlobalTemplate, "{{instance}}", dto.Instance)

		return globalLogs
	}

	orchestyLogs := strings.ReplaceAll(templates.LogsBlockOrchestyTemplate, "{{grafanaEnabled}}", strconv.FormatBool(dto.Customizations.Logs.GrafanaEnabled))

	retentionPeriod := dto.Customizations.Logs.RetentionPeriod
	if retentionPeriod == 0 {
		retentionPeriod = 178 // default ~7 days
	}
	orchestyLogs = strings.ReplaceAll(orchestyLogs, "{{retentionPeriod}}", strconv.Itoa(retentionPeriod))

	storageSize := dto.Customizations.Logs.LogsStorageSize
	if storageSize == 0 {
		storageSize = 1 // default 1Gi
	}
	orchestyLogs = strings.ReplaceAll(orchestyLogs, "{{storageSize}}", strconv.Itoa(storageSize))

	return orchestyLogs
}

func (h *Helm) buildWorkers(dto *models.InstanceDTO) string {
	workersBlock := ""
	if len(dto.Customizations.Workers) > 0 {
		workersBlock = templates.WorkersBlockHeader
		for _, worker := range dto.Customizations.Workers {
			workerBlock := strings.ReplaceAll(templates.WorkersBlockTemplate, "{{workerName}}", worker.Name)
			workerBlock = strings.ReplaceAll(workerBlock, "{{workerImage}}", worker.Image)
			workerBlock = strings.ReplaceAll(workerBlock, "{{workerSdkType}}", worker.SdkType)

			workerEnvsBlock := ""
			if len(worker.Envs) > 0 {
				workerEnvsBlock = templates.WorkersEnvsHeader
				for _, env := range worker.Envs {
					envBlock := strings.ReplaceAll(templates.WorkersEnvsTemplate, "{{key}}", env.Key)
					envBlock = strings.ReplaceAll(envBlock, "{{value}}", env.Value)
					workerEnvsBlock += envBlock
				}
			}
			workerBlock = strings.ReplaceAll(workerBlock, "{{workerEnvsBlock}}", workerEnvsBlock)

			workersBlock += workerBlock
		}
	}

	return workersBlock
}

func (h *Helm) buildResourceLimits(dto *models.InstanceDTO) string {
	if !dto.Customizations.ResourceLimits.Enabled {
		return ""
	}

	resourceLimitsBlock := strings.ReplaceAll(templates.ResourceLimitsBlockTemplate, "{{cpuLimit}}", dto.Customizations.ResourceLimits.Cpu)
	resourceLimitsBlock = strings.ReplaceAll(resourceLimitsBlock, "{{memoryLimit}}", dto.Customizations.ResourceLimits.Memory)
	resourceLimitsBlock = strings.ReplaceAll(resourceLimitsBlock, "{{limitsEnabled}}", strconv.FormatBool(dto.Customizations.ResourceLimits.Enabled))

	return resourceLimitsBlock
}

func (h *Helm) buildImageOverrides(dto *models.InstanceDTO) string {
	if dto.Customizations.Applinth.Enabled {
		imageOverridesBlock := strings.ReplaceAll(templates.ImageOverridesApplinthBlockTemplate, "{{hanabosoDockerRegistry}}", config.Orchesty.DockerRegistry)
		imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{applinthMarketplaceUiImage}}", config.Applinth.MarketplaceUiImage)
		imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{applinthBackendImage}}", config.Applinth.BackendImage)
		imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{appOrchestyVersion}}", config.Orchesty.Version)

		imageOverridesBlock = strings.ReplaceAll(templates.ImageOverridesBlockHeaderTemplate, "{{imageOverridesBlock}}", imageOverridesBlock)

		return imageOverridesBlock
	}

	imageOverridesBlock := strings.ReplaceAll(templates.ImageOverridesBlockTemplate, "{{hanabosoDockerRegistry}}", config.Orchesty.DockerRegistry)
	imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{enterpriseFrontendImage}}", config.Orchesty.EnterpriseFrontendImage)
	imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{enterpriseBackendImage}}", config.Orchesty.EnterpriseBackendImage)
	imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{tunnelProxyImage}}", config.Orchesty.TunnelProxyImage)
	imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{traceImage}}", config.Orchesty.TraceImage)
	imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{notifierImage}}", config.Orchesty.NotifierImage)
	imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{metricsCollectorImage}}", config.Orchesty.MetricsCollectorImage)
	imageOverridesBlock = strings.ReplaceAll(imageOverridesBlock, "{{appOrchestyVersion}}", config.Orchesty.Version)

	imageOverridesBlock = strings.ReplaceAll(templates.ImageOverridesBlockHeaderTemplate, "{{imageOverridesBlock}}", imageOverridesBlock)

	return imageOverridesBlock
}

func (h *Helm) getKubeConfigArgs() []string {
	if config.K8s.ClusterConfig != "" {
		return []string{"--kubeconfig", config.K8s.ClusterConfig}
	}

	return []string{}
}

func sanitizeK8sName(value string) (string, error) {
	value = strings.ToLower(strings.TrimSpace(value))
	if value == "" {
		return "", fmt.Errorf("instance display name is empty")
	}

	var builder strings.Builder
	builder.Grow(len(value))

	lastWasDash := false
	for _, char := range value {
		isLowercaseLetter := char >= 'a' && char <= 'z'
		isDigit := char >= '0' && char <= '9'

		if isLowercaseLetter || isDigit {
			builder.WriteRune(char)
			lastWasDash = false
			continue
		}

		if !lastWasDash {
			builder.WriteByte('-')
			lastWasDash = true
		}
	}

	name := strings.Trim(builder.String(), "-")
	if name == "" {
		return "", fmt.Errorf("instance display name %q does not contain any valid characters", value)
	}

	if len(name) > 63 {
		name = strings.TrimRight(name[:63], "-")
		if name == "" {
			return "", fmt.Errorf("instance display name %q produced an empty chart name after truncation", value)
		}
	}

	if !recommendedNameRegex.MatchString(name) {
		return "", fmt.Errorf("instance display name %q produced invalid chart name %q", value, name)
	}

	return name, nil
}
