package services

import (
	"fmt"
	v12 "k8s.io/api/core/v1"
	"log"
	"os"
	"strings"
	"testing"

	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/stretchr/testify/require"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"gopkg.in/yaml.v2"
	v1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/client-go/kubernetes"
	"k8s.io/client-go/kubernetes/fake"

	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
)

var (
	testClient          kubernetesClient
	topologyID          = "5dc0474e4e9acc00282bb942"
	deploymentName      = GetDeploymentName(topologyID)
	configMapName       = GetConfigMapName(topologyID)
	testConfigGenerator = config.GeneratorConfig{
		Path:              "/tmp",
		TopologyPath:      "/srv/app/topology/topology.json",
		ProjectSourcePath: "/",
		Mode:              "compose",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "demo_default",
		MultiNode:         true,
		WorkerDefaultPort: 0,
	}
)

type testDb struct {
	mockGetTopology      func(id string) (*model.Topology, error)
	mockGetTopologyNodes func(id string) ([]model.Node, error)
}

func getMockTopology() *model.Topology {
	objectID, err := primitive.ObjectIDFromHex(topologyID)
	if err != nil {
		return nil
	}
	return &model.Topology{
		ID:         objectID,
		Name:       "TestTopology",
		Version:    0,
		Descr:      "",
		Visibility: "",
		Status:     "",
		Enabled:    false,
		Bpmn:       "",
		RawBpmn:    "",
		Deleted:    false,
	}
}

func (t testDb) GetTopology(id string) (*model.Topology, error) {
	return t.mockGetTopology(id)
}

func (t testDb) GetTopologyNodes(id string) ([]model.Node, error) {
	return t.mockGetTopologyNodes(id)
}

func getTestNodes() []model.Node {

	var nodes = make([]model.Node, 0)

	id, _ := primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c12")
	var next = []model.NodeNext{
		{
			ID:   "5cc047dd4e9acc002a200c14",
			Name: "Xml_parser",
		},
	}

	nodes = append(nodes, model.Node{
		ID:       id,
		Name:     "start",
		Topology: "5cc0474e4e9acc00282bb942",
		Next:     next,
		Type:     "start",
		Handler:  "event",
		Enabled:  true,
		Deleted:  false,
	})

	id, _ = primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c13")
	nodes = append(nodes, model.Node{
		ID:       id,
		Name:     "Webhook",
		Topology: "5cc0474e4e9acc00282bb942",
		Type:     "webhook",
		Handler:  "event",
		Enabled:  true,
		Deleted:  false,
	})

	id, _ = primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c14")
	next = []model.NodeNext{
		{
			ID:   "5cc047dd4e9acc002a200c13",
			Name: "Webhook",
		},
	}

	nodes = append(nodes, model.Node{
		ID:       id,
		Name:     "Xml_parser",
		Topology: "5cc0474e4e9acc00282bb942",
		Next:     next,
		Type:     "xml_parser",
		Handler:  "action",
		Enabled:  true,
		Deleted:  false,
	})

	return nodes
}

func setup() {
	namespace := "testnamespace"
	clientSet := fake.NewSimpleClientset()
	deploymentsClient := clientSet.AppsV1().Deployments(namespace)
	configClient := clientSet.CoreV1().ConfigMaps(namespace)
	serviceClient := clientSet.CoreV1().Services(namespace)
	testClient = kubernetesClient{
		deploymentClient: deploymentsClient,
		namespace:        namespace,
		configClient:     configClient,
		serviceClient:    serviceClient,
		logger:           zap.NewLogger(),
	}
}

func TestClient_Create(t *testing.T) {
	setup()
	registry := "testregistry"
	image := "testimage"
	topologyPath := "/testtopologypath"
	const mountName = "testtopologyjson"
	command := strings.Split(getMultiBridgeStartCommand(), " ")

	containers := []model.Container{
		{
			Name:    getPodName(topologyID),
			Command: []string{command[0]},
			Resources: model.Resources{
				Limits: model.ResourceLimits{
					Memory: "128",
					CPU:    "1",
				},
				Requests: model.ResourceRequests{
					Memory: "128",
					CPU:    "1",
				},
			},
			Args:            command[1:],
			Image:           getDockerImage(registry, image),
			ImagePullPolicy: string(v12.PullAlways),
			Ports: []model.Port{
				{
					ContainerPort: 80008,
				},
			},
			VolumeMounts: []model.VolumeMount{
				{
					Name:      mountName,
					MountPath: strings.ReplaceAll(topologyPath, "/topology.json", ""),
				},
			},
		},
	}

	labels := make(map[string]string)
	labels["app"] = topologyID
	var depl = model.Deployment{
		APIVersion: "apps/v1",
		Kind:       "Deployment",
		Metadata: model.Metadata{
			Name: deploymentName,
		},
		Spec: model.Spec{
			Replicas: 0,
			Selector: model.Selector{MatchLabels: labels},
			Template: model.Template{
				Metadata: model.TemplateMetadata{
					Labels: labels,
				},
				Spec: model.TemplateSpec{
					Containers: containers,
					Volumes: []model.Volume{
						{
							Name: mountName,
							ConfigMap: model.ConfigMapVolume{
								Name: GetConfigMapName(topologyID),
							},
						},
					},
				},
			},
		},
	}
	out, err := yaml.Marshal(depl)
	if err != nil {
		t.Fatal(err)
	}

	err = testClient.create(out)
	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	d, err := testClient.deploymentClient.Get(ctx, deploymentName, v1.GetOptions{})

	if err != nil {
		t.Fatal(err)
	}

	require.Equal(t, d.Name, deploymentName)
	require.Equal(t, d.Spec.Replicas, int32Ptr(0), "Replicas should be equal 0 after creating deployment")

}

func TestClient_CreateConfigMap(t *testing.T) {
	setup()
	data := make(map[string]string)
	data["topology.json"] = "topologyjsonobject"
	configMap := model.ConfigMap{
		APIVersion: "v1",
		Kind:       "ConfigMap",
		Metadata: model.Metadata{
			Name: configMapName,
		},
		Data: data,
	}

	out, err := yaml.Marshal(configMap)

	if err != nil {
		t.Fatal(err)
	}

	err = testClient.createConfigMap(out)

	if err != nil {
		t.Fatal(err)
	}

	ctx, cancel := testClient.createContext()
	defer cancel()

	cm, err := testClient.configClient.Get(ctx, configMapName, v1.GetOptions{})

	if err != nil {
		t.Fatal(err)
	}

	require.Equal(t, cm.Name, configMapName)
}

func TestClient_CreateService(t *testing.T) {
	setup()
	s := model.DeploymentService{
		APIVersion: "v1",
		Kind:       "Service",
		Metadata: model.Metadata{
			Name: deploymentName,
		},
		Spec: model.ServiceSpec{
			Ports: []model.ServicePort{
				{
					Protocol: "TCP",
					Port:     8008,
				},
			},
			Selector: map[string]string{
				"app": deploymentName,
			},
		},
	}
	out, err := yaml.Marshal(s)

	if err != nil {
		t.Fatal(err)
	}

	err = testClient.createService(out)

	if err != nil {
		t.Fatal(err)
	}

	ctx, cancel := testClient.createContext()
	defer cancel()
	res, err := testClient.serviceClient.Get(ctx, deploymentName, v1.GetOptions{})

	if err != nil {
		t.Fatal(err)
	}

	require.Equal(t, res.Name, deploymentName)
}

func TestClient_Delete(t *testing.T) {
	setup()
	t.Run("creating deployment", TestClient_Create)
	err := testClient.delete("notthere")
	require.NotEqual(t, err, nil, "Deleting non existing deployment should fire an error")

	err = testClient.delete(deploymentName)

	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	d, err := testClient.deploymentClient.Get(ctx, deploymentName, v1.GetOptions{})
	require.NotNil(t, err, "Err cannot be null, deployment shouldnt exists")
	require.Nil(t, d, "Deployment should be nil, because it has been deleted")
}

func TestClient_DeleteConfigMap(t *testing.T) {
	setup()
	t.Run("creating config map", TestClient_CreateConfigMap)
	err := testClient.deleteConfigMap(configMapName)

	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	c, err := testClient.configClient.Get(ctx, configMapName, v1.GetOptions{})
	require.NotNil(t, err, "Err cannot be null, config map shouldnt exists")
	require.Nil(t, c, "Config Map should be nil, because it has been deleted")
}

func TestClient_DeleteService(t *testing.T) {
	setup()
	t.Run("creating service", TestClient_CreateService)
	err := testClient.deleteService(deploymentName)

	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	c, err := testClient.configClient.Get(ctx, deploymentName, v1.GetOptions{})
	require.NotNil(t, err, "Err cannot be null, config map shouldnt exists")
	require.Nil(t, c, "Config Map should be nil, because it has been deleted")
}

func TestClient_Start(t *testing.T) {
	setup()
	t.Run("Creating deployment", TestClient_Create)
	err := testClient.RunStop(topologyID, db, "start")
	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	d, err := testClient.deploymentClient.Get(ctx, deploymentName, v1.GetOptions{})
	if err != nil {
		t.Fatal(err)
	}
	require.Equal(t, d.Spec.Replicas, int32Ptr(1), "Replicas should be equal 1 after starting")
}

func TestClient_Stop(t *testing.T) {
	setup()
	t.Run("Starting deployment", TestClient_Start)
	err := testClient.RunStop(topologyID, db, "stop")
	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	d, err := testClient.deploymentClient.Get(ctx, deploymentName, v1.GetOptions{})
	if err != nil {
		t.Fatal(err)
	}
	require.Equal(t, d.Spec.Replicas, int32Ptr(0), "Replicas should be equal 0 after stopin")
}

func TestClient_Info(t *testing.T) {
	setup()
	t.Run("creating deployment", TestClient_Create)
	containers, err := testClient.Info(deploymentName)

	if err != nil {
		log.Fatal(err)
	}

	require.Equal(t, 0, len(containers))

	t.Run("deleting deployment", TestClient_Delete)

	t.Run("starting deployment", TestClient_Start)
	containers, err = testClient.Info(deploymentName)

	if err != nil {
		log.Fatal(err)
	}

	require.Equal(t, 1, len(containers))
	t.Run("deleting deployment", TestClient_Delete)
	t.Run("stopping deployment", TestClient_Stop)

	containers, err = testClient.Info(deploymentName)

	if err != nil {
		log.Fatal(err)
	}

	require.Equal(t, 0, len(containers))
}

func TestClient_DeleteAll(t *testing.T) {
	setup()
	t.Run("Generating deployement", TestClient_Generate)

	err := testClient.DeleteAll(topologyID, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return getTestNodes(), nil
		},
	}, testConfigGenerator)

	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	cm, err := testClient.configClient.Get(ctx, GetConfigMapName(topologyID), v1.GetOptions{})

	require.NotNil(t, err, "Getting deleted config map should return error")
	require.Nil(t, cm, "Deleted config map should be nil after calling get for it")

	d, err := testClient.deploymentClient.Get(ctx, GetDeploymentName(topologyID), v1.GetOptions{})

	require.NotNil(t, err, "Getting deleted deployment should return error")
	require.Nil(t, d, "Deleted deployment should be nil after calling get for it")

	s, err := testClient.serviceClient.Get(ctx, GetDeploymentName(topologyID), v1.GetOptions{})

	require.NotNil(t, err, "Getting deleted service should return error")
	require.Nil(t, s, "Deleted service should be nil after calling get for it")

	dstDir := GetDstDir(testConfigGenerator.Path, getMockTopology().GetSaveDir())
	// check whether following paths are really deleted
	files := []string{
		dstDir,
		fmt.Sprintf("%s/kubernetes-deployment.yaml", dstDir),
		fmt.Sprintf("%s/configmap.yaml", dstDir),
		fmt.Sprintf("%s/service.yaml", dstDir),
		fmt.Sprintf("%s/topology.json", dstDir),
	}

	for _, file := range files {
		if _, err := os.Stat(file); !os.IsNotExist(err) {
			t.FailNow()
		}
	}

}

func TestClient_Generate(t *testing.T) {
	setup()
	ts, err := NewTopologyService(model.NodeConfig{
		NodeConfig: getNodeConfigs(),
		Environment: model.Environment{
			DockerRegistry:      "testregistry",
			DockerPfBridgeImage: "testimages",
			RabbitMqHost:        "",
			MetricsDsn:          "",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "",
			Limits: model.Limits{
				Memory: "128Mi",
				CPU:    "100m",
			},
			Requests: model.Requests{
				Memory: "128Mi",
				CPU:    "100m",
			},
		},
	}, testConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return getTestNodes(), nil
		},
	}, topologyID)
	if err != nil {
		t.Fatal(err)
	}
	err = testClient.Generate(ts)
	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	d, err := testClient.deploymentClient.Get(ctx, GetDeploymentName(topologyID), v1.GetOptions{})
	if err != nil {
		t.Fatal(err)
	}
	require.NotNil(t, d, "Deployment cannot be nil")

	s, err := testClient.serviceClient.Get(ctx, fmt.Sprintf("topology-%s", topologyID), v1.GetOptions{})
	if err != nil {
		t.Fatal(err)
	}
	require.Equal(t, s.Spec.Selector["app"], d.Spec.Template.ObjectMeta.Labels["app"])
	require.NotNil(t, s, "Service cannot be nil")

	cm, err := testClient.configClient.Get(ctx, GetConfigMapName(topologyID), v1.GetOptions{})
	if err != nil {
		t.Fatal(err)
	}
	require.NotNil(t, cm, "Config map cannot be nil")
	dstDir := GetDstDir(testConfigGenerator.Path, ts.Topology.GetSaveDir())
	require.DirExists(t, dstDir)
	require.FileExists(t, fmt.Sprintf("%s/kubernetes-deployment.yaml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/configmap.yaml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/service.yaml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/topology.json", dstDir))
}

func TestClient_GenerateMulti(t *testing.T) {
	setup()
	testConfigGenerator.MultiNode = false
	ts, err := NewTopologyService(model.NodeConfig{
		NodeConfig: getNodeConfigs(),
		Environment: model.Environment{
			DockerRegistry:      "testregistry",
			DockerPfBridgeImage: "testimages",
			RabbitMqHost:        "",
			MetricsDsn:          "",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "",
			Limits: model.Limits{
				Memory: "128Mi",
				CPU:    "100m",
			},
			Requests: model.Requests{
				Memory: "128Mi",
				CPU:    "100m",
			},
		},
	}, testConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return getTestNodes(), nil
		},
	}, topologyID)
	if err != nil {
		t.Fatal(err)
	}
	err = testClient.Generate(ts)
	if err != nil {
		t.Fatal(err)
	}
	ctx, cancel := testClient.createContext()
	defer cancel()
	d, err := testClient.deploymentClient.Get(ctx, GetDeploymentName(topologyID), v1.GetOptions{})
	if err != nil {
		t.Fatal(err)
	}
	require.NotNil(t, d, "Deployment cannot be nil")

	s, err := testClient.serviceClient.Get(ctx, fmt.Sprintf("topology-%s", topologyID), v1.GetOptions{})
	if err != nil {
		t.Fatal(err)
	}
	require.NotNil(t, s, "Service cannot be nil")

	cm, err := testClient.configClient.Get(ctx, GetConfigMapName(topologyID), v1.GetOptions{})
	if err != nil {
		t.Fatal(err)
	}
	require.NotNil(t, cm, "Config map cannot be nil")
	dstDir := GetDstDir(testConfigGenerator.Path, ts.Topology.GetSaveDir())
	require.DirExists(t, dstDir)
	require.FileExists(t, fmt.Sprintf("%s/kubernetes-deployment.yaml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/configmap.yaml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/service.yaml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/topology.json", dstDir))
}

func TestNewKubernetesSvc(t *testing.T) {
	svc := NewKubernetesSvc(&kubernetes.Clientset{
		DiscoveryClient: nil,
	}, "test")

	require.NotNil(t, svc)
}

func TestClient_DeleteAllFails(t *testing.T) {
	setup()
	ts, err := NewTopologyService(model.NodeConfig{
		NodeConfig: getNodeConfigs(),
		Environment: model.Environment{
			DockerRegistry:      "testregistry",
			DockerPfBridgeImage: "testimages",
			RabbitMqHost:        "",
			MetricsDsn:          "",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "",
		},
	}, testConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return getTestNodes(), nil
		},
	}, topologyID)
	if err != nil {
		t.Fatal(err)
	}
	t.Run("Checking that DeleteAll fails on getting topology", func(t *testing.T) {
		err := testClient.DeleteAll("testId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return nil, fmt.Errorf("failed on purpose")
			},
			mockGetTopologyNodes: nil,
		}, testConfigGenerator)

		require.NotNil(t, err)
	})
	t.Run("Checking that DeleteAll fails on deleting service", func(t *testing.T) {
		err := testClient.DeleteAll("testId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: nil,
		}, testConfigGenerator)

		require.NotNil(t, err)
	})

	t.Run("Checking that DeleteAll fails on deleting configmap", func(t *testing.T) {
		service, err := ts.CreateDeploymentService()
		err = testClient.createService(service)
		if err != nil {
			t.Fatal(err)
		}

		err = testClient.DeleteAll(topologyID, testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: nil,
		}, testConfigGenerator)

		require.NotNil(t, err)
	})

	t.Run("Checking that DeleteAll fails on deleting deployment", func(t *testing.T) {

		service, err := ts.CreateDeploymentService()
		err = testClient.createService(service)
		if err != nil {
			t.Fatal(err)
		}

		cm, err := ts.CreateConfigMap()
		err = testClient.createConfigMap(cm)

		if err != nil {
			t.Fatal(err)
		}

		err = testClient.DeleteAll(topologyID, testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: nil,
		}, testConfigGenerator)

		require.NotNil(t, err)
	})

}

func TestClient_CreateFails(t *testing.T) {
	setup()
	err := testClient.create([]byte("error"))
	require.NotNil(t, err)
}

func TestClient_CreateServiceFails(t *testing.T) {
	setup()
	err := testClient.createService([]byte("error"))
	require.NotNil(t, err)
}

func TestClient_CreateConfigMapFails(t *testing.T) {
	setup()
	err := testClient.createConfigMap([]byte("error"))
	require.NotNil(t, err)
}

func TestClient_InfoFails(t *testing.T) {
	setup()
	_, err := testClient.Info("nonexistingdepl")
	require.NotNil(t, err)
}

func TestClient_StartFails(t *testing.T) {
	setup()
	err := testClient.start("nonexistingdepl")
	require.NotNil(t, err)
}

func TestClient_StopFails(t *testing.T) {
	setup()
	err := testClient.stop("nonexistingdepl")
	require.NotNil(t, err)
}

func TestClient_RunStopFails(t *testing.T) {
	setup()
	t.Run("Checking RunStop can fail on getting topology", func(t *testing.T) {
		err := testClient.RunStop("nonRelevantId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return nil, fmt.Errorf("throwing error for test purpose")
			},
			mockGetTopologyNodes: nil,
		}, "start")
		require.NotNil(t, err)
	})
	t.Run("Checking RunStop can fail on starting topology", func(t *testing.T) {
		err := testClient.RunStop("nonRelevantId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: nil,
		}, "start")
		require.NotNil(t, err)
	})
	t.Run("Checking RunStop can fail on stopping topology", func(t *testing.T) {
		err := testClient.RunStop("nonRelevantId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: nil,
		}, "stop")
		require.NotNil(t, err)
	})
	t.Run("Checking RunStop can fail on unknown action", func(t *testing.T) {
		err := testClient.RunStop("nonRelevantId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: nil,
		}, "nonexistingaction")
		require.NotNil(t, err)
	})
}

func TestClient_GenerateFails(t *testing.T) {
	configGenerator := config.GeneratorConfig{
		Path:              "/tmp",
		TopologyPath:      "/srv/app/topology/topology.json",
		ProjectSourcePath: "/tmp",

		Mode:              "compose",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "demo_default",
		MultiNode:         false,
		WorkerDefaultPort: 0,
	}

	nodeConfig := model.NodeConfig{
		NodeConfig: getNodeConfigs(),
		Environment: model.Environment{
			DockerRegistry:      "dkr.hanaboso.net/pipes/pipes",
			DockerPfBridgeImage: "hanaboso/bridge:dev",
			RabbitMqHost:        "test:99",
			MetricsDsn:          "",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "compose",
			Limits: model.Limits{
				Memory: "64Mi",
				CPU:    "200m",
			},
			Requests: model.Requests{
				Memory: "64Mi",
				CPU:    "200m",
			},
		},
	}

	t.Run("Check that generate fails on generate topology", func(t *testing.T) {
		setup()

		ts, err := NewTopologyService(nodeConfig, configGenerator, testDb{
			mockGetTopology: func(id string) (topology *model.Topology, err error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: func(id string) (nodes []model.Node, err error) {
				return []model.Node{}, nil
			},
		}, topologyID)
		if err != nil {
			t.Fatal(err)
		}
		err = testClient.Generate(ts)

		require.NotNil(t, err)
		require.Equal(t, "failed generating topology. Reason: error creating topology json. Reason: missing nodes", err.Error())
	})

	t.Run("Check that generate doesnt fails on creating existing config map", func(t *testing.T) {
		setup()

		ts, err := NewTopologyService(nodeConfig, configGenerator, testDb{
			mockGetTopology: func(id string) (topology *model.Topology, err error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: func(id string) (nodes []model.Node, err error) {
				return getTestNodes(), nil
			},
		}, topologyID)
		if err != nil {
			t.Fatal(err)
		}

		out, err := ts.CreateConfigMap()
		if err != nil {
			t.Fatal(err)
		}

		err = testClient.createConfigMap(out)
		if err != nil {
			t.Fatal(err)
		}
		err = testClient.Generate(ts)

		require.Nil(t, err)
	})

	t.Run("Check that generate doesnt fails on creating existing deployment", func(t *testing.T) {
		setup()

		ts, err := NewTopologyService(nodeConfig, configGenerator, testDb{
			mockGetTopology: func(id string) (topology *model.Topology, err error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: func(id string) (nodes []model.Node, err error) {
				return getTestNodes(), nil
			},
		}, topologyID)
		if err != nil {
			t.Fatal(err)
		}

		out, err := ts.CreateKubernetesDeployment()
		if err != nil {
			t.Fatal(err)
		}
		err = testClient.create(out)
		if err != nil {
			t.Fatal(err)
		}
		err = testClient.Generate(ts)

		require.Nil(t, err)
	})

	t.Run("Check that generate doesnt fails on creating existing service", func(t *testing.T) {
		setup()

		ts, err := NewTopologyService(nodeConfig, configGenerator, testDb{
			mockGetTopology: func(id string) (topology *model.Topology, err error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: func(id string) (nodes []model.Node, err error) {
				return getTestNodes(), nil
			},
		}, topologyID)
		if err != nil {
			t.Fatal(err)
		}

		out, err := ts.CreateDeploymentService()
		if err != nil {
			t.Fatal(err)
		}
		err = testClient.createService(out)
		if err != nil {
			t.Fatal(err)
		}
		err = testClient.Generate(ts)

		require.Nil(t, err)
	})
}

func TestGetKubernetesConfig(t *testing.T) {
	setup()

	cfg, err := GetKubernetesConfig(config.GeneratorConfig{
		Path:              "",
		TopologyPath:      "",
		ProjectSourcePath: "",
		Mode:              "compose",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "",
		MultiNode:         false,
		WorkerDefaultPort: 0,
	})

	require.NotNil(t, err)
	require.Nil(t, cfg)
}
