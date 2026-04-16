package config

import (
	"net/url"
	"strings"

	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"

	log "github.com/hanaboso/go-log/pkg"
)

type config struct {
	App      *app
	MongoDB  *mongoDB
	RabbitMQ *rabbitMQ
	K8s      *k8s
	Helm     *helm
	Orchesty *orchesty
	Applinth *applinth
	Kong     *kong
	GCS      *gcs
	Cloud    *cloud
}

type app struct {
	Debug bool `default:"false" env:"APP_DEBUG"`
	Port  int  `default:"8080" env:"APP_PORT"`
}

type mongoDB struct {
	DSN      string `env:"MONGODB_DSN" required:"true"`
	Hostname string `env:"MONGODB_HOSTNAME" required:"true"`
}

type rabbitMQ struct {
	Hostname       string `env:"RABBIT_HOSTNAME" required:"true"`
	ManagementPort string `env:"RABBIT_MANAGEMENT_PORT" default:"15672"`
	AdminUser      string `env:"RABBIT_ADMIN_USER" default:"guest"`
	AdminPass      string `env:"RABBIT_ADMIN_PASS" default:"guest"`
}

type k8s struct {
	ClusterConfig string `env:"K8S_CLUSTER_CONFIG" default:""`
}

type orchesty struct {
	Version                 string `env:"APP_ORCHESTY_VERSION" default:"2.1"`
	DockerRegistry          string `env:"ORCHESTY_DOCKER_REGISTRY" default:"dkr.hanaboso.net"`
	EnterpriseBackendImage  string `env:"ORCHESTY_ENTERPRISE_BACKEND_IMAGE" default:"pipes/pipes/enterprise-backend"`
	EnterpriseFrontendImage string `env:"ORCHESTY_ENTERPRISE_FRONTEND_IMAGE" default:"pipes/pipes/enterprise-frontend"`
	TunnelProxyImage        string `env:"ORCHESTY_TUNNEL_PROXY_IMAGE" default:"pipes/pipes/tunnel-proxy"`
	TraceImage              string `env:"ORCHESTY_TRACE_IMAGE" default:"pipes/pipes/trace"`
	NotifierImage           string `env:"ORCHESTY_NOTIFIER_IMAGE" default:"pipes/pipes/notifier"`
	MetricsCollectorImage   string `env:"ORCHESTY_METRICS_COLLECTOR_IMAGE" default:"pipes/pipes/metrics-collector"`
}

type applinth struct {
	MarketplaceUiImage string `env:"APPLINTH_MARKETPLACE_UI_IMAGE" default:"pipes/pipes/applinth-marketplace-ui"`
	BackendImage       string `env:"APPLINTH_BACKEND_IMAGE" default:"pipes/pipes/applinth"`
}

type helm struct {
	RootDirForFiles string `env:"HELM_ROOT_DIR_FOR_FILES" default:"/tmp/helm"`
	OrchestyVersion string `env:"HELM_ORCHESTY_VERSION" default:"~2.1.15"`
	BridgePoolKey   string `env:"HELM_BRIDGEPOOL_KEY" default:"bridgepool"`
}

type kong struct {
	Enabled  bool   `env:"KONG_ENABLED" default:"false"`
	AdminURL string `env:"KONG_ADMIN_URL" default:"http://kong:8001"`
}

type gcs struct {
	Enabled             bool   `env:"GCS_ENABLED" default:"false"`
	Location            string `env:"GCS_LOCATION" default:"eu"`
	Endpoint            string `env:"GCS_ENDPOINT" default:"storage.googleapis.com"`
	ProjectID           string `env:"GCS_PROJECT_ID" default:""`
	CredentialsFile     string `env:"GCS_CREDENTIALS_FILE" default:""`
	ServiceAccountEmail string `env:"GCS_SERVICE_ACCOUNT_EMAIL" default:""`
}

type cloud struct {
	InstancePrefix string `env:"CLOUD_INSTANCE_PREFIX" default:"prod"`
	Instance       string `env:"CLOUD_INSTANCE" default:"orchesty-instance"`
	DomainSuffix   string `env:"KONG_DOMAIN_SUFFIX" default:"eu2.cloud.orchesty.io"`
	Oauth0Domain   string `env:"CLOUD_AUTH0_DOMAIN" required:"true"`
	Oauth0Audience string `env:"CLOUD_AUTH0_AUDIENCE" required:"true"`
	Oauth0ClientId string `env:"CLOUD_AUTH0_CLIENT_ID" required:"true"`
	PullSecret     string `env:"CLOUD_PULL_SECRET" default:"hanaboso"`
}

func (g *gcs) S3Endpoint() string {
	parsed, _ := url.Parse(strings.TrimRight(g.Endpoint, "/"))

	return parsed.Host
}

var (
	App      app
	MongoDB  mongoDB
	RabbitMQ rabbitMQ
	K8s      k8s
	Helm     helm
	Orchesty orchesty
	Applinth applinth
	Kong     kong
	GCS      gcs
	Cloud    cloud
	Logger   log.Logger

	c = config{
		App:      &App,
		MongoDB:  &MongoDB,
		RabbitMQ: &RabbitMQ,
		K8s:      &K8s,
		Helm:     &Helm,
		Orchesty: &Orchesty,
		Applinth: &Applinth,
		Kong:     &Kong,
		Cloud:    &Cloud,
	}
)

func init() {
	Logger = zap.NewLogger()
	if err := configor.Load(&c); err != nil {
		Logger.Fatal(err)
	}

	if App.Debug {
		Logger.SetLevel(log.DEBUG)
	} else {
		Logger.SetLevel(log.INFO)
	}
}
