package services

import (
	"fmt"
	"testing"

	"github.com/docker/docker/api/types"
	"github.com/stretchr/testify/require"

	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
)

var (
	testDocker DockerSvc
	db         StorageSvc
)

func setupDockerTest() {
	testDocker = NewDockerSvc()
	db = testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return getTestNodes(), nil
		},
	}
}

func TestDockerClient_MultiNodeDockerCompose(t *testing.T) {
	multiConfigGenerator := config.GeneratorConfig{
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

	setupDockerTest()
	ts, err := NewTopologyService(model.NodeConfig{
		NodeConfig: getNodeConfigs(),
		Environment: model.Environment{
			DockerPfBridgeImage: "hanaboso/bridge:dev",
			RabbitMqHost:        "localhost:56",
			MetricsDsn:          "metrics:963",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "compose",
			Limits: model.Limits{
				Memory: "128M",
				CPU:    "0.5",
			},
		},
	}, multiConfigGenerator, db, topologyID)
	if err != nil {
		t.Fatal(err)
	}
	err = testDocker.Generate(ts)
	if err != nil {
		t.Fatal(err)
	}
	dstDir := GetDstDir(testConfigGenerator.Path, ts.Topology.GetSaveDir())
	require.DirExists(t, dstDir)
	require.FileExists(t, fmt.Sprintf("%s/docker-compose.yml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/topology.json", dstDir))

	testCliSvc := mockDockerCli{
		mockStopCompose: func(dstDir string) error {
			return nil
		},
		mockStartCompose: func(dstDir string) error {
			return nil
		},
		mockGetDockerTopologyInfo: func(status string, name string) (containers []types.Container, err error) {
			return []types.Container{
				getMockContainer(),
			}, nil
		},
	}

	containers, err := testDocker.RunStop(topologyID, db, testCliSvc, multiConfigGenerator, "start")

	if err != nil {
		t.Fatal(err)
	}

	require.Equal(t, 1, len(containers), "Len of running containers must be 1 after start")

	containers, err = testDocker.RunStop(topologyID, db, testCliSvc, multiConfigGenerator, "stop")

	require.Equal(t, 0, len(containers), "Len of running containers must be 0 after stop")

	containers, err = testDocker.RunStop(topologyID, db, testCliSvc, multiConfigGenerator, "stopstart")

	require.NotNil(t, err)

	err = testDocker.Delete(topologyID, db, multiConfigGenerator)
	if err != nil {
		t.Fatal(err)
	}

}

func TestDockerClient_DockerCompose(t *testing.T) {
	configGenerator := config.GeneratorConfig{
		Path:                     "/tmp",
		TopologyPath:             "/srv/app/topology/topology.json",
		ProjectSourcePath:        "/tmp",
		Mode:                     "compose",
		ClusterConfig:            "",
		Namespace:                "",
		Prefix:                   "",
		Network:                  "demo_default",
		MultiNode:                false,
		WorkerDefaultPort:        0,
		WorkerDefaultLimitMemory: "2048",
		WorkerDefaultLimitCPU:    "2",
	}

	setupDockerTest()
	ts, err := NewTopologyService(model.NodeConfig{
		NodeConfig: getNodeConfigs(),
		Environment: model.Environment{
			DockerPfBridgeImage: "hanaboso/bridge:dev",
			RabbitMqHost:        "test:99",
			MetricsDsn:          "",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "compose",
		},
	}, configGenerator, db, topologyID)
	if err != nil {
		t.Fatal(err)
	}
	err = testDocker.Generate(ts)
	if err != nil {
		t.Fatal(err)
	}
	dstDir := GetDstDir(testConfigGenerator.Path, ts.Topology.GetSaveDir())
	require.DirExists(t, dstDir)
	require.FileExists(t, fmt.Sprintf("%s/docker-compose.yml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/topology.json", dstDir))

	testCliSvc := mockDockerCli{
		mockStopCompose: func(dstDir string) error {
			return nil
		},
		mockStartCompose: func(dstDir string) error {
			return nil
		},
		mockGetDockerTopologyInfo: func(status string, name string) (containers []types.Container, err error) {
			return []types.Container{
				getMockContainer(),
				getMockContainer(),
				getMockContainer(),
			}, nil
		},
	}

	containers, err := testDocker.RunStop(topologyID, db, testCliSvc, configGenerator, "start")

	if err != nil {
		t.Fatal(err)
	}

	require.Equal(t, 3, len(containers), "Len of running containers must be 1 after start")

	containers, err = testDocker.RunStop(topologyID, db, testCliSvc, configGenerator, "stop")

	testCliSvc.mockGetDockerTopologyInfo = func(status string, name string) (containers []types.Container, err error) {
		return []types.Container{}, nil
	}

	require.Equal(t, 0, len(containers), "Len of running containers must be 0 after stop")

	containers, err = testDocker.RunStop(topologyID, db, testCliSvc, configGenerator, "stopstart")

	require.NotNil(t, err)

	err = testDocker.Delete(topologyID, db, configGenerator)
	if err != nil {
		t.Fatal(err)
	}

}

func TestDockerClient_Swarm(t *testing.T) {
	setupDockerTest()
	configGenerator := config.GeneratorConfig{
		Path:                     "/tmp",
		TopologyPath:             "/srv/app/topology/topology.json",
		ProjectSourcePath:        "/tmp",
		Mode:                     "swarm",
		ClusterConfig:            "",
		Namespace:                "",
		Prefix:                   "pre4",
		Network:                  "demo_default_swarm",
		MultiNode:                true,
		WorkerDefaultPort:        800,
		WorkerDefaultLimitMemory: "2048",
		WorkerDefaultLimitCPU:    "2",
	}

	ts, err := NewTopologyService(model.NodeConfig{
		NodeConfig: getNodeConfigs(),
		Environment: model.Environment{
			DockerPfBridgeImage: "hanaboso/bridge:dev",
			RabbitMqHost:        "test:99",
			MetricsDsn:          "",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "swarm",
		},
	}, configGenerator, db, topologyID)
	if err != nil {
		t.Fatal(err)
	}
	err = testDocker.Generate(ts)
	if err != nil {
		t.Fatal(err)
	}
	dstDir := GetDstDir(testConfigGenerator.Path, ts.Topology.GetSaveDir())
	require.DirExists(t, dstDir)
	require.FileExists(t, fmt.Sprintf("%s/docker-compose.yml", dstDir))
	require.FileExists(t, fmt.Sprintf("%s/topology.json", dstDir))

	testSwarmCli := mockDockerCli{
		mockRunSwarm: func(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
			return nil
		},
		mockStopSwarm: func(topology *model.Topology, prefix string) error {
			return nil
		},
		mockGetSwarmTopologyInfo: func(status string, name string) (containers []types.Container, err error) {
			return []types.Container{
				getMockContainer(),
			}, nil
		},
		mockCreateSwarmConfig: func(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
			return nil
		},
		mockRemoveSwarmConfig: func(topology *model.Topology, prefix string) error {
			return nil
		},
	}

	containers, err := testDocker.RunStopSwarm(topologyID, db, testSwarmCli, configGenerator, "start")

	if err != nil {
		t.Fatal(err)
	}

	require.Equal(t, 1, len(containers), "Len of running containers must be 1 after start")

	testSwarmCli.mockGetSwarmTopologyInfo = func(status string, name string) (containers []types.Container, err error) {
		return []types.Container{}, nil
	}

	containers, err = testDocker.RunStopSwarm(topologyID, db, testSwarmCli, configGenerator, "stop")
	require.Equal(t, 0, len(containers), "Len of running containers must be 0 after stop")

	containers, err = testDocker.RunStopSwarm(topologyID, db, testSwarmCli, configGenerator, "stopstart")
	require.NotNil(t, err)

	err = testDocker.DeleteSwarm(topologyID, db, testSwarmCli, configGenerator)
	if err != nil {
		t.Fatal(err)
	}

}

func TestDockerClient_RunStopFails(t *testing.T) {
	setupDockerTest()
	configGenerator := config.GeneratorConfig{
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

	testDockerCli := mockDockerCli{}

	t.Run("Testing that starting composer fails on getting topology", func(t *testing.T) {
		_, err := testDocker.RunStop("unknownId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), fmt.Errorf("cant return topology for test purpose")
			},
			mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
				return getTestNodes(), nil
			},
		}, testDockerCli, configGenerator, "start")
		require.NotNil(t, err)
		require.Equal(t, "getting topology unknownId failed. Reason: cant return topology for test purpose", err.Error())
	})

	t.Run("Testing that start composer fails on start", func(t *testing.T) {
		testDockerCli.mockStartCompose = func(dstDir string) error {
			return fmt.Errorf("failed to start docker compose")
		}
		configGenerator.Path = "/missingpath"
		_, err := testDocker.RunStop("unknownId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
				return getTestNodes(), nil
			},
		}, testDockerCli, config.GeneratorConfig{
			Path:              "/missingpath",
			TopologyPath:      "/srv/app/topology/topology.json",
			ProjectSourcePath: "/",
			Mode:              "compose",
			ClusterConfig:     "",
			Namespace:         "",
			Prefix:            "",
			Network:           "demo_default",
			MultiNode:         true,
			WorkerDefaultPort: 0,
		}, "start")
		require.NotNil(t, err)
	})

	t.Run("Testing that start composer fails on getting topology info", func(t *testing.T) {
		testDockerCli.mockStartCompose = func(dstDir string) error {
			return nil
		}
		testDockerCli.mockGetDockerTopologyInfo = func(status string, name string) (containers []types.Container, err error) {
			return []types.Container{}, fmt.Errorf("failed to get topology info")
		}
		configGenerator.Path = "/missingpath"
		_, err := testDocker.RunStop("unknownId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
				return getTestNodes(), nil
			},
		}, testDockerCli, config.GeneratorConfig{
			Path:              "/missingpath",
			TopologyPath:      "/srv/app/topology/topology.json",
			ProjectSourcePath: "/",
			Mode:              "compose",
			ClusterConfig:     "",
			Namespace:         "",
			Prefix:            "",
			Network:           "demo_default",
			MultiNode:         true,
			WorkerDefaultPort: 0,
		}, "start")
		require.NotNil(t, err)
		require.Equal(t, "error getting running containers, Reason: failed to get topology info", err.Error())
	})

	t.Run("Testing that docker compose stop fails", func(t *testing.T) {
		testDockerCli.mockStopCompose = func(dstDir string) error {
			return fmt.Errorf("failed stopping docker compose")
		}

		_, err := testDocker.RunStop("unknownId", testDb{
			mockGetTopology: func(id string) (topology *model.Topology, e error) {
				return getMockTopology(), nil
			},
			mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
				return getTestNodes(), nil
			},
		}, testDockerCli, config.GeneratorConfig{
			Path:              "/missingpath",
			TopologyPath:      "/srv/app/topology/topology.json",
			ProjectSourcePath: "/",
			Mode:              "compose",
			ClusterConfig:     "",
			Namespace:         "",
			Prefix:            "",
			Network:           "demo_default",
			MultiNode:         true,
			WorkerDefaultPort: 0,
		}, "stop")
		require.NotNil(t, err)
		require.Equal(t, "error stopping dockerCli composer. Reason: failed stopping docker compose", err.Error())
	})
}

func TestDockerClient_GenerateFails(t *testing.T) {
	setupDockerTest()

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
			DockerPfBridgeImage: "hanaboso/bridge:dev",
			RabbitMqHost:        "test:99",
			MetricsDsn:          "",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "compose",
		},
	}

	t.Run("Check that generate fails on generate topology", func(t *testing.T) {
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
		err = testDocker.Generate(ts)
		require.NotNil(t, err)
		require.Equal(t, "error generating topology. Reason: error creating topology json. Reason: missing nodes", err.Error())
	})

}

func TestDockerClient_DeleteFails(t *testing.T) {
	setupDockerTest()

	err := testDocker.Delete(topologyID, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), fmt.Errorf("cant return topology for test purpose")
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return getTestNodes(), nil
		}}, config.GeneratorConfig{
		Path:              "",
		TopologyPath:      "",
		ProjectSourcePath: "",
		Mode:              "",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "",
		MultiNode:         false,
		WorkerDefaultPort: 0,
	})
	require.NotNil(t, err)
	require.Equal(t, "failed to get topology. Reason: cant return topology for test purpose", err.Error())
}

func TestDockerClient_DeleteSwarmFails(t *testing.T) {
	setupDockerTest()
	configGenerator := config.GeneratorConfig{
		Path:              "",
		TopologyPath:      "",
		ProjectSourcePath: "",
		Mode:              "",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "",
		MultiNode:         false,
		WorkerDefaultPort: 0,
	}
	testSwarmCLi := mockDockerCli{
		mockStopSwarm: func(topology *model.Topology, prefix string) error {
			return fmt.Errorf("stopping swarm failed")
		}}

	t.Run("testing delete swarm fails on getting topology", func(t *testing.T) {
		err := testDocker.DeleteSwarm(
			topologyID,
			testDb{
				mockGetTopology: func(id string) (topology *model.Topology, e error) {
					return getMockTopology(), fmt.Errorf("cant return topology for test purpose")
				},
				mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
					return getTestNodes(), nil
				}},
			testSwarmCLi,
			configGenerator,
		)
		require.NotNil(t, err)
		require.Equal(t, "getting topology 5dc0474e4e9acc00282bb942 failed. Reason: cant return topology for test purpose", err.Error())
	})
}

func TestDockerClient_RunStopSwarmFails(t *testing.T) {
	setupDockerTest()
	testSwarmCLi := mockDockerCli{}
	configGenerator := config.GeneratorConfig{
		Path:              "",
		TopologyPath:      "",
		ProjectSourcePath: "",
		Mode:              "",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "",
		MultiNode:         false,
		WorkerDefaultPort: 0,
	}

	t.Run("Testing that RunStopSwarm fails on getting topology", func(t *testing.T) {
		_, err := testDocker.RunStopSwarm(
			topologyID,
			testDb{
				mockGetTopology: func(id string) (topology *model.Topology, e error) {
					return getMockTopology(), fmt.Errorf("cant return topology for test purpose")
				},
				mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
					return getTestNodes(), nil
				}},
			testSwarmCLi,
			configGenerator,
			"start")
		require.NotNil(t, err)
		require.Equal(t, "getting topology 5dc0474e4e9acc00282bb942 failed. Reason: cant return topology for test purpose", err.Error())
	})

	t.Run("Testing that RunStopSwarm fails on creating config", func(t *testing.T) {
		testSwarmCLi.mockCreateSwarmConfig = func(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
			return fmt.Errorf("error creating swarm config")
		}
		_, err := testDocker.RunStopSwarm(
			topologyID,
			testDb{
				mockGetTopology: func(id string) (topology *model.Topology, e error) {
					return getMockTopology(), nil
				},
				mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
					return getTestNodes(), nil
				}},
			testSwarmCLi,
			configGenerator,
			"start")
		require.NotNil(t, err)
		require.Equal(t, "failed to create swarm config, Reason: error creating swarm config", err.Error())
	})

	t.Run("Testing that RunStopSwarm fails on run", func(t *testing.T) {
		testSwarmCLi.mockCreateSwarmConfig = func(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
			return nil
		}
		testSwarmCLi.mockRunSwarm = func(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
			return fmt.Errorf("error starting swarm")
		}
		_, err := testDocker.RunStopSwarm(
			topologyID,
			testDb{
				mockGetTopology: func(id string) (topology *model.Topology, e error) {
					return getMockTopology(), nil
				},
				mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
					return getTestNodes(), nil
				}},
			testSwarmCLi,
			configGenerator,
			"start")
		require.NotNil(t, err)
		require.Equal(t, "failed to run swarm. Reason: error starting swarm", err.Error())
	})

	t.Run("Testing that RunStopSwarm fails on getting swarm topology info", func(t *testing.T) {
		testSwarmCLi.mockCreateSwarmConfig = func(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
			return nil
		}
		testSwarmCLi.mockRunSwarm = func(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
			return nil
		}
		testSwarmCLi.mockGetSwarmTopologyInfo = func(status string, name string) (containers []types.Container, err error) {
			return []types.Container{}, fmt.Errorf("error get swarm topology info")
		}

		_, err := testDocker.RunStopSwarm(
			topologyID,
			testDb{
				mockGetTopology: func(id string) (topology *model.Topology, e error) {
					return getMockTopology(), nil
				},
				mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
					return getTestNodes(), nil
				}},
			testSwarmCLi,
			configGenerator,
			"start")
		require.NotNil(t, err)
		require.Equal(t, "Error getting running containers, Reason: error get swarm topology info", err.Error())
	})

	t.Run("Testing that RunStopSwarm fails on removing config", func(t *testing.T) {
		testSwarmCLi.mockRemoveSwarmConfig = func(topology *model.Topology, prefix string) error {
			return fmt.Errorf("error removing swarm config")
		}

		_, err := testDocker.RunStopSwarm(
			topologyID,
			testDb{
				mockGetTopology: func(id string) (topology *model.Topology, e error) {
					return getMockTopology(), nil
				},
				mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
					return getTestNodes(), nil
				}},
			testSwarmCLi,
			configGenerator,
			"stop")
		require.NotNil(t, err)
		require.Equal(t, "failed to remove swarm config. Reason: error removing swarm config", err.Error())
	})

	t.Run("Testing that RunStopSwarm fails on stopping swarm config", func(t *testing.T) {
		testSwarmCLi.mockRemoveSwarmConfig = func(topology *model.Topology, prefix string) error {
			return nil
		}
		testSwarmCLi.mockStopSwarm = func(topology *model.Topology, prefix string) error {
			return fmt.Errorf("error stopping swarm")
		}

		_, err := testDocker.RunStopSwarm(
			topologyID,
			testDb{
				mockGetTopology: func(id string) (topology *model.Topology, e error) {
					return getMockTopology(), nil
				},
				mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
					return getTestNodes(), nil
				}},
			testSwarmCLi,
			configGenerator,
			"stop")
		require.NotNil(t, err)
		require.Equal(t, "failed to stop swarm. Reason: error stopping swarm", err.Error())
	})
}
