package kubernetes

import (
	"context"
	"flag"
	"fmt"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/models"
	"metrics-collector/pkg/storage"
	"metrics-collector/pkg/utils"

	metav1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/client-go/kubernetes"
	"k8s.io/client-go/rest"
	"k8s.io/client-go/tools/clientcmd"
	metricsv1beta1 "k8s.io/metrics/pkg/client/clientset/versioned"
)

type Collector struct {
	clientset     kubernetes.Interface
	metricsClient metricsv1beta1.Interface
}

func NewCollector() (*Collector, error) {
	// get config from k8s cluster itself
	cfg, err := rest.InClusterConfig()
	if err != nil {
		return nil, fmt.Errorf("error getting config from cluster: %v", err)
	}

	// check if path to k8s was given in env param. if so, use its config
	if clusterConfig := config.Kubernetes.ClusterConfig; clusterConfig != "" {
		kubeconfig := flag.String("kubeconfig", clusterConfig, "absolute path to the kubeconfig file")
		flag.Parse()

		cfg, err = clientcmd.BuildConfigFromFlags("", *kubeconfig)
		if err != nil {
			return nil, fmt.Errorf("error building kubernetes config from flags: %v", err)
		}
	}

	if err != nil {
		return nil, fmt.Errorf("failed to load kubeconfig: %w", err)
	}

	clientset, err := kubernetes.NewForConfig(cfg)
	if err != nil {
		return nil, fmt.Errorf("failed to create clientset: %w", err)
	}

	metricsClient, err := metricsv1beta1.NewForConfig(cfg)
	if err != nil {
		return nil, fmt.Errorf("failed to create metrics client: %w", err)
	}

	return &Collector{
		clientset:     clientset,
		metricsClient: metricsClient,
	}, nil
}

func (c *Collector) Name() string {
	return "Kubernetes"
}

func (c *Collector) Collect(ctx context.Context, repo *storage.MongoRepository) error {
	metric, err := c.fetchMetrics(ctx)
	if err != nil {
		config.Logger.ErrorWrap("failed to fetch K8s metrics", err)
		return err
	}

	if err := repo.SaveK8sMetric(ctx, metric); err != nil {
		config.Logger.ErrorWrap("failed to save K8s metric", err)
		return err
	}

	if err := c.aggregateMetrics(ctx, repo); err != nil {
		config.Logger.ErrorWrap("failed to aggregate K8s metrics", err)
	}

	config.Logger.Debug("K8s metrics collected", map[string]interface{}{
		"vcpu":      metric.TotalVCPU,
		"memory_mb": metric.TotalMemoryMB,
	})

	return nil
}

func (c *Collector) fetchMetrics(ctx context.Context) (*models.K8sMetric, error) {
	podMetrics, err := c.metricsClient.MetricsV1beta1().PodMetricses(config.Kubernetes.Namespace).List(ctx, metav1.ListOptions{})
	if err != nil {
		return nil, fmt.Errorf("failed to get pod metrics: %w", err)
	}

	var totalCpuNanoCores int64
	var totalMemoryBytes int64

	for _, podMetric := range podMetrics.Items {
		for _, container := range podMetric.Containers {
			cpuQuantity := container.Usage.Cpu()
			memQuantity := container.Usage.Memory()

			totalCpuNanoCores += cpuQuantity.MilliValue() * 1e6
			totalMemoryBytes += memQuantity.Value()
		}
	}

	totalVCPU := utils.RoundFloat(float64(totalCpuNanoCores)/1e9, 2)
	totalMemoryMB := utils.RoundFloat(float64(totalMemoryBytes)/(1024*1024), 2)

	return &models.K8sMetric{
		TotalVCPU:     totalVCPU,
		TotalMemoryMB: totalMemoryMB,
		Timestamp:     time.Now(),
	}, nil
}

func (c *Collector) aggregateMetrics(ctx context.Context, repo *storage.MongoRepository) error {
	now := time.Now()
	metrics, err := repo.GetK8sMetricsForMonth(ctx)
	if err != nil {
		return fmt.Errorf("failed to get metrics for month: %w", err)
	}

	if len(metrics) == 0 {
		return nil
	}

	var sumVCPU, sumMemory float64
	var maxVCPU, maxMemory float64

	for _, m := range metrics {
		sumVCPU += m.TotalVCPU
		sumMemory += m.TotalMemoryMB

		if m.TotalVCPU > maxVCPU {
			maxVCPU = m.TotalVCPU
		}
		if m.TotalMemoryMB > maxMemory {
			maxMemory = m.TotalMemoryMB
		}
	}

	count := float64(len(metrics))

	currentMonth := now.Format("2006-01")

	agg := &models.K8sAggregation{
		Month:       currentMonth,
		AvgVCPU:     utils.RoundFloat(sumVCPU/count, 2),
		MaxVCPU:     utils.RoundFloat(maxVCPU, 2),
		AvgMemoryMB: utils.RoundFloat(sumMemory/count, 2),
		MaxMemoryMB: utils.RoundFloat(maxMemory, 2),
		LastUpdated: now,
	}

	return repo.SaveK8sAggregation(ctx, agg)
}
