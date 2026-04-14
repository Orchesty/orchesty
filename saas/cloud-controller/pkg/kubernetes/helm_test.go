package kubernetes

import (
	"errors"
	"os"
	"path/filepath"
	"reflect"
	"strings"
	"testing"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
)

func withHelmConfig(t *testing.T, rootDir, version, bridgePoolKey, clusterConfig string) {
	t.Helper()

	originalRootDir := config.Helm.RootDirForFiles
	originalVersion := config.Helm.OrchestyVersion
	originalBridgePoolKey := config.Helm.BridgePoolKey
	originalClusterConfig := config.K8s.ClusterConfig

	config.Helm.RootDirForFiles = rootDir
	config.Helm.OrchestyVersion = version
	config.Helm.BridgePoolKey = bridgePoolKey
	config.K8s.ClusterConfig = clusterConfig

	t.Cleanup(func() {
		config.Helm.RootDirForFiles = originalRootDir
		config.Helm.OrchestyVersion = originalVersion
		config.Helm.BridgePoolKey = originalBridgePoolKey
		config.K8s.ClusterConfig = originalClusterConfig
	})
}

func testHelmDTO() *models.InstanceDTO {
	return &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Test Instance",
		InstanceUrlPrefix:   "test-prefix",
		Customizations: models.Customizations{
			Workers: []models.Worker{
				{
					Name:    "default",
					Image:   "hanaboso/demo-worker:latest",
					SdkType: "nodejs",
				},
			},
		},
	}
}

func TestCreateFilesWithWorkers(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	helm := NewHelm()
	dto := testHelmDTO()

	if err := helm.createFiles(tempDir, dto); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	chartBytes, err := os.ReadFile(filepath.Join(tempDir, "Chart.yaml"))
	if err != nil {
		t.Fatalf("expected Chart.yaml, got %v", err)
	}
	valuesBytes, err := os.ReadFile(filepath.Join(tempDir, "Values.yaml"))
	if err != nil {
		t.Fatalf("expected Values.yaml, got %v", err)
	}

	chart := string(chartBytes)
	values := string(valuesBytes)

	if !strings.Contains(chart, "name: test-instance") || !strings.Contains(chart, `version: "~2.1.15"`) {
		t.Fatalf("unexpected chart content: %s", chart)
	}
	if !strings.Contains(chart, "description: Test Instance Applinth Implementation") {
		t.Fatalf("unexpected chart description content: %s", chart)
	}
	if !strings.Contains(values, "sdk: nodejs") || !strings.Contains(values, "image: hanaboso/demo-worker:latest") {
		t.Fatalf("expected workers block in values: %s", values)
	}
	if !strings.Contains(values, "bridgepool: \"true\"") {
		t.Fatalf("expected bridgepool key in values: %s", values)
	}
	if !strings.Contains(values, "api-test-prefix-instance-test.") {
		t.Fatalf("expected instanceUrlPrefix expanded in backend_url: %s", values)
	}
}

func TestCreateFilesWithoutWorkers(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	helm := NewHelm()
	dto := &models.InstanceDTO{Instance: "instance-test", InstanceDisplayName: "Test Instance", InstanceUrlPrefix: "test-prefix"}

	if err := helm.createFiles(tempDir, dto); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	valuesBytes, err := os.ReadFile(filepath.Join(tempDir, "Values.yaml"))
	if err != nil {
		t.Fatalf("expected Values.yaml, got %v", err)
	}

	if strings.Contains(string(valuesBytes), "workers:") {
		t.Fatalf("expected values without workers block, got %s", string(valuesBytes))
	}
}

func TestGetKubeConfigArgs(t *testing.T) {
	withHelmConfig(t, t.TempDir(), "~2.1.15", "bridgepool", "/tmp/kubeconfig")

	helm := NewHelm()
	args := helm.getKubeConfigArgs()
	if !reflect.DeepEqual(args, []string{"--kubeconfig", "/tmp/kubeconfig"}) {
		t.Fatalf("unexpected kubeconfig args: %v", args)
	}
}

func TestGetKubeConfigArgsWithoutClusterConfig(t *testing.T) {
	withHelmConfig(t, t.TempDir(), "~2.1.15", "bridgepool", "")

	helm := NewHelm()
	args := helm.getKubeConfigArgs()
	if len(args) != 0 {
		t.Fatalf("expected no kubeconfig args, got %v", args)
	}
}

func TestInstallCallsHelmCommands(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "/tmp/kubeconfig")

	var calls [][]string
	helm := &Helm{
		executeFn: func(args ...string) (string, error) {
			copied := append([]string(nil), args...)
			calls = append(calls, copied)
			return "ok", nil
		},
	}

	if err := helm.Install(testHelmDTO()); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(calls) != 2 {
		t.Fatalf("expected two helm calls, got %d", len(calls))
	}
	if !reflect.DeepEqual(calls[0], []string{"dependency", "build", filepath.Join(tempDir, "instance-test")}) {
		t.Fatalf("unexpected dependency call: %v", calls[0])
	}
	if calls[1][0] != "upgrade" || calls[1][1] != "-i" || calls[1][2] != "orchesty" {
		t.Fatalf("unexpected install call prefix: %v", calls[1])
	}
	if calls[1][len(calls[1])-2] != "--kubeconfig" || calls[1][len(calls[1])-1] != "/tmp/kubeconfig" {
		t.Fatalf("expected kubeconfig args at end, got %v", calls[1])
	}
}

func TestInstallReturnsWrappedDependencyError(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	helm := &Helm{
		executeFn: func(args ...string) (string, error) {
			if len(args) >= 2 && args[0] == "dependency" && args[1] == "build" {
				return "dependency failed", errors.New("boom")
			}
			return "ok", nil
		},
	}

	err := helm.Install(testHelmDTO())
	if err == nil {
		t.Fatal("expected error")
	}
	if !strings.Contains(err.Error(), "helm dependency build failed") {
		t.Fatalf("unexpected error: %v", err)
	}
}

func TestInstallReturnsWrappedUpgradeError(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	callCount := 0
	helm := &Helm{
		executeFn: func(args ...string) (string, error) {
			callCount++
			if callCount == 1 {
				return "dependency ok", nil
			}
			return "upgrade failed", errors.New("boom")
		},
	}

	err := helm.Install(testHelmDTO())
	if err == nil {
		t.Fatal("expected error")
	}
	if !strings.Contains(err.Error(), "helm install failed") {
		t.Fatalf("unexpected error: %v", err)
	}
	if !strings.Contains(err.Error(), "output: upgrade failed") {
		t.Fatalf("expected wrapped helm output, got %v", err)
	}
}

func TestCreateFilesWithValkeyLimit(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	helm := NewHelm()
	dto := &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Test Instance",
		InstanceUrlPrefix:   "test-prefix",
		Customizations: models.Customizations{
			Valkey: models.Valkey{
				Enabled: true,
				PersistentStorage: struct {
					Enabled bool `json:"enabled"`
					Size    int  `json:"size,omitempty"`
				}{Enabled: true, Size: 4},
				Limit: models.ValkeyLimit{
					CPU:     500,
					Memory:  1,
					Storage: 2,
				},
			},
		},
	}

	if err := helm.createFiles(tempDir, dto); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	valuesBytes, err := os.ReadFile(filepath.Join(tempDir, "Values.yaml"))
	if err != nil {
		t.Fatalf("expected Values.yaml, got %v", err)
	}

	values := string(valuesBytes)

	if !strings.Contains(values, "enabled: true") {
		t.Fatalf("expected valkey enabled: true in values: %s", values)
	}
	if !strings.Contains(values, "requestedSize: 4Gi") {
		t.Fatalf("expected requestedSize: 4Gi in values: %s", values)
	}
	if !strings.Contains(values, "cpu: 500m") {
		t.Fatalf("expected cpu: 500m in values: %s", values)
	}
	if !strings.Contains(values, "memory: 1Gi") {
		t.Fatalf("expected memory: 1Gi in values: %s", values)
	}
	if !strings.Contains(values, "ephemeral-storage: 2Gi") {
		t.Fatalf("expected ephemeral-storage: 2Gi in values: %s", values)
	}
}

func TestCreateFilesWithWorkerEnvs(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	helm := NewHelm()
	dto := &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Test Instance",
		InstanceUrlPrefix:   "test-prefix",
		Customizations: models.Customizations{
			Workers: []models.Worker{
				{
					Name:    "my-worker",
					Image:   "hanaboso/worker:latest",
					SdkType: "nodejs",
					Envs: []models.WorkerEnv{
						{Key: "API_KEY", Value: "secret123"},
						{Key: "DEBUG", Value: "true"},
					},
				},
			},
		},
	}

	if err := helm.createFiles(tempDir, dto); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	valuesBytes, err := os.ReadFile(filepath.Join(tempDir, "Values.yaml"))
	if err != nil {
		t.Fatalf("expected Values.yaml, got %v", err)
	}

	values := string(valuesBytes)

	if !strings.Contains(values, "extraEnv:") {
		t.Fatalf("expected extraEnv block in values: %s", values)
	}
	if !strings.Contains(values, "API_KEY:") || !strings.Contains(values, "value: secret123") {
		t.Fatalf("expected API_KEY env in values: %s", values)
	}
	if !strings.Contains(values, "DEBUG:") || !strings.Contains(values, "value: true") {
		t.Fatalf("expected DEBUG env in values: %s", values)
	}
}

func TestCreateFilesReplacesAppOrchestyVersionPlaceholder(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	originalOrchestyVersion := config.Orchesty.Version
	config.Orchesty.Version = "9.9.9-test"
	t.Cleanup(func() {
		config.Orchesty.Version = originalOrchestyVersion
	})

	helm := NewHelm()
	dto := &models.InstanceDTO{Instance: "instance-test", InstanceDisplayName: "Test Instance", InstanceUrlPrefix: "test-prefix"}

	if err := helm.createFiles(tempDir, dto); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	valuesBytes, err := os.ReadFile(filepath.Join(tempDir, "Values.yaml"))
	if err != nil {
		t.Fatalf("expected Values.yaml, got %v", err)
	}

	values := string(valuesBytes)

	if strings.Contains(values, "{{appOrchestyVersion}}") {
		t.Fatalf("expected appOrchestyVersion placeholder to be replaced, got %s", values)
	}
	if !strings.Contains(values, "orchestyVersion: \"9.9.9-test\"") {
		t.Fatalf("expected replaced orchesty version in values, got %s", values)
	}
}

func TestSanitizeK8sName(t *testing.T) {
	tests := []struct {
		name     string
		input    string
		expected string
		hasError bool
	}{
		{name: "spaces and uppercase", input: "Test Instance", expected: "test-instance"},
		{name: "special characters", input: "  Demo@Prod! 2026  ", expected: "demo-prod-2026"},
		{name: "only invalid characters", input: "@@@", hasError: true},
		{name: "empty string", input: "   ", hasError: true},
		{name: "trims and deduplicates dashes", input: "---A__B---", expected: "a-b"},
		{name: "max length 63", input: strings.Repeat("a", 70), expected: strings.Repeat("a", 63)},
	}

	for _, test := range tests {
		t.Run(test.name, func(t *testing.T) {
			result, err := sanitizeK8sName(test.input)
			if test.hasError {
				if err == nil {
					t.Fatalf("expected error, got nil")
				}

				return
			}

			if err != nil {
				t.Fatalf("expected no error, got %v", err)
			}

			if result != test.expected {
				t.Fatalf("expected %q, got %q", test.expected, result)
			}
		})
	}
}

func TestCreateFilesSanitizesChartName(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	helm := NewHelm()
	dto := &models.InstanceDTO{Instance: "instance-test", InstanceDisplayName: "Demo Instance @ EU", InstanceUrlPrefix: "test-prefix"}

	if err := helm.createFiles(tempDir, dto); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	chartBytes, err := os.ReadFile(filepath.Join(tempDir, "Chart.yaml"))
	if err != nil {
		t.Fatalf("expected Chart.yaml, got %v", err)
	}

	chart := string(chartBytes)
	if !strings.Contains(chart, "name: demo-instance-eu") {
		t.Fatalf("expected sanitized chart name, got %s", chart)
	}
	if !strings.Contains(chart, "description: Demo Instance @ EU Applinth Implementation") {
		t.Fatalf("expected original display name in chart description, got %s", chart)
	}
}

func TestCreateFilesReturnsErrorForInvalidChartName(t *testing.T) {
	tempDir := t.TempDir()
	withHelmConfig(t, tempDir, "~2.1.15", "bridgepool", "")

	helm := NewHelm()
	dto := &models.InstanceDTO{Instance: "instance-test", InstanceDisplayName: "@@@"}

	err := helm.createFiles(tempDir, dto)
	if err == nil {
		t.Fatal("expected error")
	}
	if !strings.Contains(err.Error(), "invalid instance display name") {
		t.Fatalf("unexpected error: %v", err)
	}
}
