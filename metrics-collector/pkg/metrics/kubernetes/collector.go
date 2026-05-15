package kubernetes

import (
	"context"
	"fmt"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/metrics"
	"metrics-collector/pkg/models"
	"metrics-collector/pkg/utils"

	metav1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/client-go/kubernetes"
	"k8s.io/client-go/rest"
	"k8s.io/client-go/tools/clientcmd"
	metricsv1beta1 "k8s.io/metrics/pkg/client/clientset/versioned"
)

const CollectorName = "Kubernetes"

const collectorCtxTimeout = 10 * time.Second

type Collector struct {
	clientset        kubernetes.Interface
	metricsClient    metricsv1beta1.Interface
	lastAggregatedAt time.Time
}

func NewCollector() (*Collector, error) {
	var cfg *rest.Config
	var err error

	if clusterConfig := config.Kubernetes.ClusterConfig; clusterConfig != "" {
		cfg, err = clientcmd.BuildConfigFromFlags("", clusterConfig)
		if err != nil {
			return nil, fmt.Errorf("error building kubernetes config from flags: %v", err)
		}
	} else {
		cfg, err = rest.InClusterConfig()
		if err != nil {
			return nil, fmt.Errorf("error getting config from cluster: %v", err)
		}
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
	return CollectorName
}

func (c *Collector) Collect(ctx context.Context, repo metrics.Repository) error {
	metric, err := c.fetchMetrics(ctx)
	if err != nil {
		config.Logger.ErrorWrap("failed to fetch K8s metrics", err)
		return err
	}

	if err := repo.SaveK8sMetric(ctx, metric); err != nil {
		config.Logger.ErrorWrap("failed to save K8s metric", err)
		return err
	}

	now := time.Now()
	if now.Sub(c.lastAggregatedAt) >= time.Hour {
		if err := c.aggregateMetrics(ctx, repo); err != nil {
			config.Logger.ErrorWrap("failed to aggregate K8s metrics", err)
		}
		c.lastAggregatedAt = now
	}

	config.Logger.Debug("K8s metrics collected", map[string]interface{}{
		"vcpu":      metric.TotalVCPU,
		"memory_mb": metric.TotalMemoryMB,
	})

	return nil
}

func (c *Collector) fetchMetrics(ctx context.Context) (*models.K8sMetric, error) {
	podMetricsCtx, cancel := context.WithTimeout(ctx, collectorCtxTimeout)
	defer cancel()

	podMetrics, err := c.metricsClient.MetricsV1beta1().PodMetricses(config.Kubernetes.Namespace).List(podMetricsCtx, metav1.ListOptions{})
	if err != nil {
		return nil, fmt.Errorf("failed to get pod metrics: %w", err)
	}

	podsCtx, podsCancel := context.WithTimeout(ctx, collectorCtxTimeout)
	defer podsCancel()

	pods, err := c.clientset.CoreV1().Pods(config.Kubernetes.Namespace).List(podsCtx, metav1.ListOptions{})
	if err != nil {
		return nil, fmt.Errorf("failed to get pods: %w", err)
	}

	totalContainers := 0
	for i := range podMetrics.Items {
		totalContainers += len(podMetrics.Items[i].Containers)
	}

	usageCPUByContainer := make(map[string]int64, totalContainers)
	usageMemByContainer := make(map[string]int64, totalContainers)

	for i := range podMetrics.Items {
		podMetric := &podMetrics.Items[i]
		for j := range podMetric.Containers {
			container := &podMetric.Containers[j]
			key := fmt.Sprintf("%s/%s", podMetric.Name, container.Name)
			usageCPUByContainer[key] = container.Usage.Cpu().MilliValue() * 1e6
			usageMemByContainer[key] = container.Usage.Memory().Value()
		}
	}

	var totalCpuNanoCores int64
	var totalMemoryBytes int64

	for i := range pods.Items {
		pod := &pods.Items[i]
		if pod.Status.Phase == "Succeeded" || pod.Status.Phase == "Failed" {
			continue
		}

		for j := range pod.Spec.Containers {
			container := &pod.Spec.Containers[j]
			key := pod.Name + "/" + container.Name

			reqCPU := container.Resources.Requests.Cpu().MilliValue() * 1e6
			reqMem := container.Resources.Requests.Memory().Value()

			actCPU := usageCPUByContainer[key]
			actMem := usageMemByContainer[key]

			effectiveCPU := reqCPU
			if actCPU > effectiveCPU {
				effectiveCPU = actCPU
			}

			effectiveMem := reqMem
			if actMem > effectiveMem {
				effectiveMem = actMem
			}

			totalCpuNanoCores += effectiveCPU
			totalMemoryBytes += effectiveMem
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

func (c *Collector) aggregateMetrics(ctx context.Context, repo metrics.Repository) error {
	agg, err := repo.GetK8sMonthlyAggregation(ctx)
	if err != nil {
		return fmt.Errorf("failed to aggregate metrics in MongoDB: %w", err)
	}
	if agg == nil {
		return nil
	}
	return repo.SaveK8sAggregation(ctx, agg)
}
