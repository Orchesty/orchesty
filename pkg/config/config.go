package config

import (
	"os"

	"topology-generator/pkg/model"

	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	mongoConfig struct {
		Dsn      string `default:"" env:"MONGO_DSN"`
		Topology string `default:"Topology" env:"MONGO_TOPOLOGY"`
		Node     string `default:"Node" env:"MONGO_NODE"`
	}

	apiConfig struct {
		Host  string `default:"0.0.0.0:8080" env:"API_HOST"`
		Debug bool   `default:"false" env:"APP_DEBUG"`
	}

	// GeneratorConfig GeneratorConfig
	GeneratorConfig struct {
		Path              string        `default:"/opt/srv/topology" env:"GENERATOR_PATH"`
		TopologyPath      string        `default:"/srv/app/topology/topology.json" env:"TOPOLOGY_PATH"` // for node configuration, path in docker
		ProjectSourcePath string        `default:"/" env:"PROJECT_SOURCE_PATH"`                         // path where is stored local files relevant to docker.sock
		Mode              model.Adapter `default:"compose" env:"GENERATOR_MODE"`
		ClusterConfig     string        `default:"" env:"K8S_CLUSTER_CONFIG"`
		Namespace         string        `default:"default" env:"K8S_NAMESPACE"`
		Prefix            string        `default:"dev" env:"DEPLOYMENT_PREFIX"`
		Network           string        `default:"client" env:"GENERATOR_NETWORK"`
		MultiNode         bool          `default:"true" env:"MULTI_NODE"`
		WorkerDefaultPort int           `default:"8088" env:"WORKER_DEFAULT_PORT"`
	}

	config struct {
		Mongo     *mongoConfig
		API       *apiConfig
		Generator *GeneratorConfig
	}
)

var (
	// Mongo Mongo
	Mongo mongoConfig
	// API API
	API apiConfig
	// Generator Generator
	Generator GeneratorConfig
	// Logger Logger
	Logger log.Logger
	c      = config{&Mongo, &API, &Generator}
)

func init() {
	Logger = zap.NewLogger()

	if err := os.Setenv("CONFIGOR_ENV_PREFIX", "-"); err != nil {
		Logger.Fatal(err)
	}
	if err := configor.Load(&c); err != nil {
		Logger.Fatal(err)
	}

	if API.Debug {
		Logger.SetLevel(log.DEBUG)
	} else {
		Logger.SetLevel(log.INFO)
	}
}
