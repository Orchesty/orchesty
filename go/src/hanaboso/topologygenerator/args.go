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

		workerXmlParserHost string
		workerFtpHost       string
		workerEmailHost     string
		workerMapperHost    string
		workerApiHost       string
		workerConnectorHost string
		workerWebhookHost   string
		workerCustomHost    string
		workerSignalHost    string
		workerXmlParserPort int
		workerFtpPort       int
		workerEmailPort     int
		workerMapperPort    int
		workerApiPort       int
		workerConnectorPort int
		workerWebhookPort   int
		workerCustomPort    int
		workerSignalPort    int
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

	viper.SetDefault("worker.xml_parser.host", "xml-parser-api")
	viper.SetDefault("worker.ftp.host", "ftp-api")
	viper.SetDefault("worker.email.host", "mailer-api")
	viper.SetDefault("worker.mapper.host", "mapper-api")
	viper.SetDefault("worker.api.host", "monolith-api")
	viper.SetDefault("worker.connector.host", "monolith-api")
	viper.SetDefault("worker.webhook.host", "monolith-api")
	viper.SetDefault("worker.custom.host", "monolith-api")
	viper.SetDefault("worker.signal.host", "monolith-api")
	viper.SetDefault("worker.user.host", "monolith-api")
	viper.SetDefault("worker.xml_parser.port", 80)
	viper.SetDefault("worker.ftp.port", 80)
	viper.SetDefault("worker.email.port", 80)
	viper.SetDefault("worker.mapper.port", 80)
	viper.SetDefault("worker.api.port", 80)
	viper.SetDefault("worker.connector.port", 80)
	viper.SetDefault("worker.webhook.port", 80)
	viper.SetDefault("worker.custom.port", 80)
	viper.SetDefault("worker.signal.port", 80)
	viper.SetDefault("worker.user.port", 80)

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

	pflag.StringVar(&workerXmlParserHost, "worker-xml-parser-host", "xml-parser-api", "worker's xml parser host or define WORKER_XML_PARSER_HOST")
	pflag.StringVar(&workerFtpHost, "worker-ftp-host", "ftp-api", "worker's ftp host or define WORKER_API_HOST")
	pflag.StringVar(&workerEmailHost, "worker-email-host", "mailer-api", "worker's mailer host or define WORKER_MAILER_HOST")
	pflag.StringVar(&workerMapperHost, "worker-mapper-host", "mapper-api", "worker's mapper host or define WORKER_MAPPER_HOST")
	pflag.StringVar(&workerApiHost, "worker-api-host", "monolith-api", "worker's api host or define WORKER_API_HOST")
	pflag.StringVar(&workerConnectorHost, "worker-connector-host", "monolith-api", "worker's connector host or define WORKER_CONNECTOR_HOST")
	pflag.StringVar(&workerWebhookHost, "worker-webhook-host", "monolith-api", "worker's webhook host or define WORKER_WEBHOOK_HOST")
	pflag.StringVar(&workerCustomHost, "worker-custom-host", "monolith-api", "worker's custom host or define WORKER_CUSTOM_HOST")
	pflag.StringVar(&workerSignalHost, "worker-signal-host", "monolith-api", "worker's signal host or define WORKER_SIGNAL_HOST")
	pflag.IntVar(&workerXmlParserPort, "worker-xml-parser-port", 80, "worker's xml parser port or define WORKER_XML_PARSER_PORT")
	pflag.IntVar(&workerFtpPort, "worker-ftp-port", 80, "worker's ftp port or define WORKER_API_PORT")
	pflag.IntVar(&workerEmailPort, "worker-email-port", 80, "worker's mailer port or define WORKER_EMAIL_PORT")
	pflag.IntVar(&workerMapperPort, "worker-mapper-port", 80, "worker's mapper port or define WORKER_MAPPER_PORT")
	pflag.IntVar(&workerApiPort, "worker-api-port", 80, "worker's api port or define WORKER_API_PORT")
	pflag.IntVar(&workerConnectorPort, "worker-connector-port", 80, "worker's connector port or define WORKER_CONNECTOR_PORT")
	pflag.IntVar(&workerWebhookPort, "worker-webhook-port", 80, "worker's webhook port or define WORKER_WEBHOOK_PORT")
	pflag.IntVar(&workerCustomPort, "worker-custom-port", 80, "worker's custom port or define WORKER_CUSTOM_PORT")
	pflag.IntVar(&workerSignalPort, "worker-signal-port", 80, "worker's signal port or define WORKER_SIGNAL_PORT")

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

	if os.Getenv("WORKER_XML_PARSER_HOST") != "" {
		viper.Set("worker.xml_parser.host", os.Getenv("WORKER_XML_PARSER_HOST"))
	}

	if os.Getenv("WORKER_FTP_HOST") != "" {
		viper.Set("worker.ftp.host", os.Getenv("WORKER_FTP_HOST"))
	}

	if os.Getenv("WORKER_EMAIL_HOST") != "" {
		viper.Set("worker.email.host", os.Getenv("WORKER_EMAIL_HOST"))
	}

	if os.Getenv("WORKER_MAPPER_HOST") != "" {
		viper.Set("worker.mapper.host", os.Getenv("WORKER_MAPPER_HOST"))
	}

	if os.Getenv("WORKER_API_HOST") != "" {
		viper.Set("worker.api.host", os.Getenv("WORKER_API_HOST"))
	}

	if os.Getenv("WORKER_CONNECTOR_HOST") != "" {
		viper.Set("worker.connector.host", os.Getenv("WORKER_CONNECTOR_HOST"))
	}

	if os.Getenv("WORKER_WEBHOOK_HOST") != "" {
		viper.Set("worker.webhook.host", os.Getenv("WORKER_WEBHOOK_HOST"))
	}

	if os.Getenv("WORKER_CUSTOM_HOST") != "" {
		viper.Set("worker.custom.host", os.Getenv("WORKER_CUSTOM_HOST"))
	}

	if os.Getenv("WORKER_SIGNAL_HOST") != "" {
		viper.Set("worker.signal.host", os.Getenv("WORKER_SIGNAL_HOST"))
	}

	if os.Getenv("WORKER_XML_PARSER_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_XML_PARSER_PORT"))
		viper.Set("worker.xml_parser.port", port)
	}

	if os.Getenv("WORKER_FTP_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_FTP_PORT"))
		viper.Set("worker.ftp.port", port)
	}

	if os.Getenv("WORKER_EMAIL_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_EMAIL_PORT"))
		viper.Set("worker.email.port", port)
	}

	if os.Getenv("WORKER_MAPPER_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_MAPPER_PORT"))
		viper.Set("worker.mapper.port", port)
	}

	if os.Getenv("WORKER_API_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_API_PORT"))
		viper.Set("worker.api.port", port)
	}

	if os.Getenv("WORKER_CONNECTOR_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_CONNECTOR_PORT"))
		viper.Set("worker.connector.port", port)
	}

	if os.Getenv("WORKER_WEBHOOK_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_WEBHOOK_PORT"))
		viper.Set("worker.webhook.port", port)
	}

	if os.Getenv("WORKER_CUSTOM_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_CUSTOM_PORT"))
		viper.Set("worker.custom.port", port)
	}

	if os.Getenv("WORKER_SIGNAL_PORT") != "" {
		port, _ := strconv.Atoi(os.Getenv("WORKER_SIGNAL_PORT"))
		viper.Set("worker.signal.port", port)
	}
}
