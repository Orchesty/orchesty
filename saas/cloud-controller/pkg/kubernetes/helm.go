package kubernetes

import (
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/kubernetes/templates"
	"cloud-controller/pkg/models"
)

const (
	helmBinary = "helm"
)

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
	output, err := h.execute("dependency", "build", path)
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
	chart := strings.ReplaceAll(templates.ChartTemplate, "{{name}}", dto.InstanceDisplayName)
	chart = strings.ReplaceAll(chart, "{{orchestyVersion}}", config.Helm.OrchestyVersion)

	if err := os.WriteFile(filepath.Join(path, "Chart.yaml"), []byte(chart), 0o600); err != nil {
		return err
	}

	values := strings.ReplaceAll(templates.ValuesTemplate, "{{valkeyBlock}}", h.buildValkey(dto))
	values = strings.ReplaceAll(values, "{{logsBlockOrchesty}}", h.buildLogs(dto, false))
	values = strings.ReplaceAll(values, "{{logsBlockGlobal}}", h.buildLogs(dto, true))
	values = strings.ReplaceAll(values, "{{workersBlock}}", h.buildWorkers(dto))

	values = strings.ReplaceAll(values, "{{instance}}", dto.Instance)
	values = strings.ReplaceAll(values, "{{appOrchestyVersion}}", config.Orchesty.Version)
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

func (h *Helm) getKubeConfigArgs() []string {
	if config.K8s.ClusterConfig != "" {
		return []string{"--kubeconfig", config.K8s.ClusterConfig}
	}

	return []string{}
}
