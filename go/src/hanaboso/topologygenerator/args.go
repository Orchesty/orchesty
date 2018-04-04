package main

import (
	"hanaboso/topologygenerator/log"
	golog "log"
	"os"
	"strconv"

	"github.com/spf13/pflag"
	"github.com/spf13/viper"
)

func init() {
	version, err := strconv.ParseFloat(os.Getenv("DOCKER_API_VERSION"), 64)

	if err != nil {
		golog.Fatalln("Please set environment DOCKER_API_VERSION")
	}

	if version*100 < 129 {
		golog.Fatalln("Minimum support docker version is 1.30")
	}

	parseArgs()
}

func parseArgs() {

	viper.SetConfigType("yaml")
	viper.SetConfigName("config")
	viper.AddConfigPath(".")
	viper.AddConfigPath("/etc/default/topology-generator")
	err := viper.ReadInConfig()

	if err != nil {
		log.Fatalf("Fatal error config file: %s not exist, load default", err)
	}

	var (
		mongoHost              string
		mongoPort              int
		mongoDb                string
		serviceHost            string
		servicePort            int
		metricsHost            string
		metricsPort            int
		generatorPath          string
		generatorMode          string
		swarmPrefix            string
		generatorMultimode     bool
		generatorNetwork       string
		generatorDockerVersion string
		rabbitmqHost           string
		rabbitmqPort           int
		rabbitmqUser           string
		rabbitmqPass           string
		rabbitmqVhost          string
		projectPath            string
	)

	viper.SetDefault("mongodb.host", "localhost")
	viper.SetDefault("mongodb.port", 27017)
	viper.SetDefault("mongodb.database", "clever-connectors")
	viper.SetDefault("mongodb.topology-collection", "Topology")
	viper.SetDefault("mongodb.node-collection", "Node")
	viper.SetDefault("rabbitmq.host", "localhost")
	viper.SetDefault("rabbitmq.port", 5672)
	viper.SetDefault("rabbitmq.user", "guest")
	viper.SetDefault("rabbitmq.pass", "guest")
	viper.SetDefault("rabbitmq.vhost", "/")
	viper.SetDefault("service.host", "0.0.0.0")
	viper.SetDefault("service.port", 80)
	viper.SetDefault("metrics.host", "kapacitor")
	viper.SetDefault("metrics.port", 9100)
	viper.SetDefault("generator.path", "/opt/srv/topology")
	viper.SetDefault("generator.mode", "compose")
	viper.SetDefault("generator.multimode", true)
	viper.SetDefault("generator.network_name", "default")
	viper.SetDefault("generator.version", "2")
	viper.SetDefault("generator.topology-prefix", "dev")
	viper.SetDefault("generator.topology-json-path", "/srv/app/topology/topology.json")

	//TODO: refactor
	// connect to defaults
	pflag.StringVar(&mongoHost, "mongo-host", "localhost", "address of mongodb server or define MONGO_HOST")
	pflag.IntVar(&mongoPort, "mongo-port", 27017, "port of mongodb server or define MONGO_PORT")
	pflag.StringVar(&mongoDb, "mongo-db", "clever-connectors", "db of mongodb server or define MONGO_DATABASE")
	pflag.StringVar(&serviceHost, "service-host", "0.0.0.0", "server listen on or define SERVICE_HOST")
	pflag.IntVar(&servicePort, "service-port", 8080, "server port listen on or define SERVICE_PORT")
	pflag.StringVar(&metricsHost, "metrics-host", "kapacitor", "address of kapacitor server or define METRICS_HOST")
	pflag.IntVar(&metricsPort, "metrics-port", 9100, "port of kapacitor or define METRICS_PORT")
	pflag.StringVar(&generatorPath, "generator-path", "/opt/srv/topology", "path to save generated topology or define GENERATOR_PATH")
	pflag.StringVar(&generatorMode, "generator-mode", "compose", "generator mode or GENERATOR_MODE")
	pflag.StringVar(&generatorNetwork, "generator-network", "default", "generator network or GENERATOR_NETWORK")
	pflag.StringVar(&generatorDockerVersion, "generator-docker-version", "2", "generator docker version or GENERATOR_DOCKER_VERSION")
	pflag.StringVar(&swarmPrefix, "generator-topology-prefix", "dev", "swarm stack prefix or DEPLOYMENT_PREFIX")
	pflag.BoolVar(&generatorMultimode, "generator-multimode", true, "use in multi bridge mode or MULTIMODE")
	pflag.StringVar(&rabbitmqHost, "rabbitmq-host", "localhost", "address of rabbitmq server or define RABBITMQ_HOST")
	pflag.IntVar(&rabbitmqPort, "rabbitmq-port", 5672, "port port listen on or define RABBITMQ_PORT")
	pflag.StringVar(&rabbitmqUser, "rabbitmq-user", "guest", "user of rabbitmq server or define RABBITMQ_USER")
	pflag.StringVar(&rabbitmqPass, "rabbitmq-pass", "guest", "pass of rabbitmq server or define RABBITMQ_PASS")
	pflag.StringVar(&rabbitmqVhost, "rabbitmq-vhost", "/", "vhost of rabbitmq server or define RABBITMQ_VHOST")
	pflag.StringVar(&projectPath, "project-path", "/", "project path or define PROJECT_SOURCE_PATH")

	pflag.Parse()

	viper.BindPFlags(pflag.CommandLine)

	if os.Getenv("MONGO_HOST") != "" {
		mongoHost = os.Getenv("MONGO_HOST")
		viper.Set("mongodb.host", mongoHost)
	}

	if os.Getenv("MONGO_PORT") != "" {
		mongoPort, _ = strconv.Atoi(os.Getenv("MONGO_PORT"))
		viper.Set("mongodb.port", mongoPort)
	}

	if os.Getenv("MONGO_DATABASE") != "" {
		database := os.Getenv("MONGO_DATABASE")
		viper.Set("mongodb.database", database)
	}

	if os.Getenv("SERVICE_HOST") != "" {
		serviceHost = os.Getenv("SERVICE_HOST")
		viper.Set("service.host", serviceHost)
	}

	if os.Getenv("SERVICE_PORT") != "" {
		servicePort, _ = strconv.Atoi(os.Getenv("SERVICE_PORT"))
		viper.Set("service.port", servicePort)
	}

	if os.Getenv("METRICS_HOST") != "" {
		metricsHost = os.Getenv("METRICS_HOST")
		viper.Set("metrics.host", metricsHost)
	}

	if os.Getenv("METRICS_PORT") != "" {
		metricsPort, _ = strconv.Atoi(os.Getenv("METRICS_PORT"))
		viper.Set("metrics.port", metricsPort)
	}

	if os.Getenv("GENERATOR_PATH") != "" {
		generatorPath = os.Getenv("GENERATOR_PATH")
		viper.Set("generator.path", generatorPath)
	}

	if os.Getenv("GENERATOR_MODE") != "" {
		generatorMode = os.Getenv("GENERATOR_MODE")
		viper.Set("generator.mode", generatorMode)
	}

	if os.Getenv("DEPLOYMENT_PREFIX") != "" {
		swarmPrefix = os.Getenv("DEPLOYMENT_PREFIX")
		viper.Set("generator.topology-prefix", swarmPrefix)
	}

	if os.Getenv("MULTIMODE") != "" {
		multimode := os.Getenv("MULTIMODE")
		viper.Set("generator.multimode", multimode)
	}

	if os.Getenv("GENERATOR_NETWORK") != "" {
		network := os.Getenv("GENERATOR_NETWORK")
		viper.Set("generator.network_name", network)
	}

	if os.Getenv("GENERATOR_DOCKER_VERSION") != "" {
		dockerVersion := os.Getenv("GENERATOR_DOCKER_VERSION")
		viper.Set("generator.version", dockerVersion)
	}

	if os.Getenv("RABBITMQ_HOST") != "" {
		rabbitHost := os.Getenv("RABBITMQ_HOST")
		viper.Set("rabbitmq.host", rabbitHost)
	}

	if os.Getenv("RABBITMQ_PORT") != "" {
		rabbitPort, _ := strconv.Atoi(os.Getenv("RABBITMQ_PORT"))
		viper.Set("service.port", rabbitPort)
	}

	if os.Getenv("RABBITMQ_USER") != "" {
		rabbituser := os.Getenv("RABBITMQ_USER")
		viper.Set("rabbitmq.user", rabbituser)
	}

	if os.Getenv("RABBITMQ_PASS") != "" {
		rabbitPass := os.Getenv("RABBITMQ_PASS")
		viper.Set("rabbitmq.pass", rabbitPass)
	}

	if os.Getenv("PROJECT_SOURCE_PATH") != "" {
		projectSourcePath := os.Getenv("PROJECT_SOURCE_PATH")
		viper.Set("generator.project-path", projectSourcePath)
	}
}
