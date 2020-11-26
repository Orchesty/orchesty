package services

import (
	"flag"
	"fmt"
	log "github.com/hanaboso/go-log/pkg"
	appsV1 "k8s.io/api/apps/v1"
	coreV1 "k8s.io/api/core/v1"
	metaV1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/client-go/kubernetes"
	"k8s.io/client-go/kubernetes/scheme"
	typedAppsV1 "k8s.io/client-go/kubernetes/typed/apps/v1"
	typedCoreV1 "k8s.io/client-go/kubernetes/typed/core/v1"
	"k8s.io/client-go/rest"
	"k8s.io/client-go/tools/clientcmd"
	"k8s.io/client-go/util/retry"
	"strings"
	"topology-generator/pkg/config"
	"topology-generator/pkg/fscommands"
	"topology-generator/pkg/model"
)

type kubernetesClient struct {
	deploymentClient typedAppsV1.DeploymentInterface
	configClient     typedCoreV1.ConfigMapInterface
	serviceClient    typedCoreV1.ServiceInterface
	namespace        string
	logger           log.Logger
}

// DeleteAll DeleteAll
func (c kubernetesClient) DeleteAll(topologyID string, db StorageSvc, generatorConfig config.GeneratorConfig) error {
	topology, err := db.GetTopology(topologyID)
	if err != nil {
		return fmt.Errorf("error getting topology %s from db. Reason: %v", topologyID, err)
	}
	err = c.deleteService(topology.GetMultiNodeName())
	if err != nil {
		return fmt.Errorf("deleting service failed. Reason: %v", err)
	}

	err = c.deleteConfigMap(GetConfigMapName(topologyID))
	if err != nil {
		return fmt.Errorf("deleting config map failed. Reason: %v", err)
	}

	err = c.delete(GetDeploymentName(topologyID))
	if err != nil {
		return fmt.Errorf("deleting deployment failed. Reason: %v", err)
	}

	dstDir := GetDstDir(generatorConfig.Path, topology.GetSaveDir())
	err = fscommands.RemoveDirectory(dstDir)
	if err != nil {
		return fmt.Errorf("removing %s failed. Reason: %v", dstDir, err)
	}
	return nil
}

// RunStop RunStop
func (c kubernetesClient) RunStop(topologyID string, db StorageSvc, action string) error {
	topology, err := db.GetTopology(topologyID)

	if err != nil {
		return fmt.Errorf("error getting topology %s", topologyID)
	}

	switch action {
	case "start":
		err = c.start(GetDeploymentName(topology.ID.Hex()))

		if err != nil {
			return fmt.Errorf("error starting kubernetes deployment: %v", err)
		}
		return nil
	case "stop":
		err = c.stop(GetDeploymentName(topology.ID.Hex()))

		if err != nil {
			return fmt.Errorf("error stoping kubernetes deployment: %v", err)
		}
		return nil
	}
	return fmt.Errorf("action %s is not allowed", action)
}

// Generate Generate
func (c kubernetesClient) Generate(ts *TopologyService) error {
	err := ts.GenerateTopology()
	if err != nil {
		return fmt.Errorf("failed generating topology. Reason: %v", err)
	}
	dstFile := GetDstDir(ts.generatorConfig.Path, ts.Topology.GetSaveDir())
	configMap, err := ts.CreateConfigMap()
	if err != nil {
		return fmt.Errorf("creating configmap yaml content[topology_id=%s] failed. Reason: %v", ts.Topology.ID.Hex(), err)
	}
	if err := fscommands.WriteFile(
		dstFile,
		"configmap.yaml",
		configMap,
	); err != nil {
		return fmt.Errorf("writing topology[id=%s, file=configmap.json] failed. Reason: %v", ts.Topology.ID.Hex(), err)
	}

	err = c.createConfigMap(configMap)
	if err != nil {
		return fmt.Errorf("creating config map failed. Reason: %v", err)
	}

	kubernetesDeployment, err := ts.CreateKubernetesDeployment()
	if err != nil {
		return fmt.Errorf("creating deployment yaml content[topology_id=%s] failed. Reason: %v", ts.Topology.ID.Hex(), err)
	}
	if err := fscommands.WriteFile(
		dstFile,
		"kubernetes-deployment.yaml",
		kubernetesDeployment,
	); err != nil {
		return fmt.Errorf("writing topology[id=%s, file=kubernetes-deployment.json] failed. Reason: %v", ts.Topology.ID.Hex(), err)
	}

	err = c.create(kubernetesDeployment)
	if err != nil {
		return fmt.Errorf("creating deployment failed. Reason: %v", err)
	}
	c.logContext(nil).Debug("Save kubernetes-deployment.yml to %s", dstFile)

	service, err := ts.CreateDeploymentService()
	if err != nil {
		return fmt.Errorf("creating yaml content of service[topology_id=%s] failed. Reason: %v", ts.Topology.ID.Hex(), err)
	}
	if err := fscommands.WriteFile(
		dstFile,
		"service.yaml",
		service,
	); err != nil {
		return fmt.Errorf("writing topology[id=%s, file=service.yaml] failed. Reason: %v", ts.Topology.ID.Hex(), err)
	}

	err = c.createService(service)
	if err != nil {
		return fmt.Errorf("creating service in cluster failed. Reason: %v", err)
	}

	return nil
}

func (c kubernetesClient) createService(obj []byte) error {
	decode := scheme.Codecs.UniversalDeserializer().Decode
	res, _, err := decode(obj, nil, nil)
	if err != nil {
		return fmt.Errorf("error decoding new Service from file because: %v", err)
	}
	s := res.(*coreV1.Service)
	_, err = c.serviceClient.Create(s)
	// if already exists, dont throw error
	if err != nil && strings.Contains(err.Error(), "already exists") {
		return nil
	}
	return err
}

func (c kubernetesClient) deleteService(name string) error {
	deletePolicy := metaV1.DeletePropagationForeground

	return c.serviceClient.Delete(name, &metaV1.DeleteOptions{
		PropagationPolicy: &deletePolicy,
	})
}

func (c kubernetesClient) deleteConfigMap(name string) error {
	deletePolicy := metaV1.DeletePropagationForeground

	return c.configClient.Delete(name, &metaV1.DeleteOptions{
		PropagationPolicy: &deletePolicy,
	})
}

// Info Info
func (c kubernetesClient) Info(name string) ([]coreV1.Container, error) {
	deployment, err := c.deploymentClient.Get(name, metaV1.GetOptions{})
	if err != nil {
		return nil, fmt.Errorf("error getting deployement. Reason: %v", err)
	}
	// for current k8s implementation more than 0 replicas means that containers are running
	if *deployment.Spec.Replicas > int32(0) {
		return deployment.Spec.Template.Spec.Containers, nil
	}
	return []coreV1.Container{}, nil
}

func (c kubernetesClient) createConfigMap(obj []byte) error {
	decode := scheme.Codecs.UniversalDeserializer().Decode
	res, _, err := decode(obj, nil, nil)
	if err != nil {
		return fmt.Errorf("error decoding new Config map from file because: %v", err)
	}
	cm := res.(*coreV1.ConfigMap)
	_, err = c.configClient.Create(cm)
	// if already exists, dont throw error
	if err != nil && strings.Contains(err.Error(), "already exists") {
		return nil
	}
	return err
}

func (c kubernetesClient) create(obj []byte) error {
	decode := scheme.Codecs.UniversalDeserializer().Decode
	res, _, err := decode(obj, nil, nil)
	if err != nil {
		return fmt.Errorf("error decoding new Deployment from file because: %v", err)
	}
	deployment := res.(*appsV1.Deployment)
	_, err = c.deploymentClient.Create(deployment)
	// if already exists, dont throw error
	if err != nil && strings.Contains(err.Error(), "already exists") {
		return nil
	}
	return err
}

func (c kubernetesClient) start(name string) error {
	retryErr := retry.RetryOnConflict(retry.DefaultRetry, func() error {
		deployment, err := c.deploymentClient.Get(name, metaV1.GetOptions{})
		if err != nil {
			return fmt.Errorf("error getting deployement. Reason: %v", err)
		}
		deployment.Spec.Replicas = int32Ptr(1)
		_, updateErr := c.deploymentClient.Update(deployment)

		return updateErr
	})

	if retryErr != nil {
		return fmt.Errorf("start failed: %v", retryErr)
	}

	return nil
}

func (c kubernetesClient) stop(name string) error {
	retryErr := retry.RetryOnConflict(retry.DefaultRetry, func() error {
		deployment, err := c.deploymentClient.Get(name, metaV1.GetOptions{})
		if err != nil {
			return fmt.Errorf("error getting deployement. Reason: %v", err)
		}
		deployment.Spec.Replicas = new(int32)
		_, updateErr := c.deploymentClient.Update(deployment)
		return updateErr
	})
	if retryErr != nil {
		return fmt.Errorf("stop failed: %v", retryErr)
	}
	return nil
}

func (c kubernetesClient) delete(name string) error {
	deletePolicy := metaV1.DeletePropagationForeground

	return c.deploymentClient.Delete(name, &metaV1.DeleteOptions{
		PropagationPolicy: &deletePolicy,
	})
}

func (c kubernetesClient) logContext(data map[string]interface{}) log.Logger {
	if data == nil {
		data = make(map[string]interface{})
	}

	data["service"] = "topology-generator"
	data["type"] = "mongodb"

	return c.logger.WithFields(data)
}

// KubernetesSvc KubernetesSvc
type KubernetesSvc interface {
	create(obj []byte) error
	createConfigMap(obj []byte) error
	deleteConfigMap(name string) error
	start(name string) error
	stop(name string) error
	delete(name string) error
	Info(name string) ([]coreV1.Container, error)
	createService(obj []byte) error
	deleteService(name string) error
	Generate(ts *TopologyService) error
	RunStop(topologyID string, db StorageSvc, action string) error
	DeleteAll(topologyID string, db StorageSvc, generatorConfig config.GeneratorConfig) error
}

// NewKubernetesSvc NewKubernetesSvc
func NewKubernetesSvc(clientSet *kubernetes.Clientset, namespace string) KubernetesSvc {
	deploymentsClient := clientSet.AppsV1().Deployments(namespace)
	configClient := clientSet.CoreV1().ConfigMaps(namespace)
	serviceClient := clientSet.CoreV1().Services(namespace)
	return &kubernetesClient{
		deploymentClient: deploymentsClient,
		namespace:        namespace,
		configClient:     configClient,
		serviceClient:    serviceClient,
		logger:           config.Logger,
	}
}

// GetKubernetesConfig GetKubernetesConfig
func GetKubernetesConfig(config config.GeneratorConfig) (*rest.Config, error) {
	if config.Mode != model.ModeKubernetes {
		return nil, fmt.Errorf("mode %s is not compatible with kubernetes", config.Mode)
	}

	// get config from k8s cluster itself
	cfg, err := rest.InClusterConfig()
	if err != nil {
		return nil, fmt.Errorf("error getting config from cluster: %v", err)
	}
	// check if path to k8s was given in env param. if so, use its config
	if clusterConfig := config.ClusterConfig; clusterConfig != "" {
		kubeconfig := flag.String("kubeconfig", clusterConfig, "absolute path to the kubeconfig file")
		flag.Parse()

		cfg, err = clientcmd.BuildConfigFromFlags("", *kubeconfig)
		if err != nil {
			return nil, fmt.Errorf("error building kubernetes config from flags: %v", err)
		}
	}
	return cfg, nil

}

func int32Ptr(i int32) *int32 { return &i }

func getKubernetPortName(nodeID string) string {
	length := len(nodeID)
	return nodeID[length-10 : length]
}
