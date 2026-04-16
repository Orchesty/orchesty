package models

import (
	"crypto/ecdsa"
	"crypto/elliptic"
	"crypto/rand"
	"crypto/x509"
	"encoding/pem"
	"errors"
	"fmt"
	"math/big"
	"regexp"
	"strings"
)

const maxInstanceURLPrefixLength = 20

const InstancePrefix = "instance-"

const charsetFull = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
const charsetLower = "abcdefghijklmnopqrstuvwxyz0123456789"

const defaultUserName = "orchesty@hanaboso.com"

var emailRegex = regexp.MustCompile(`^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$`)

var ErrInvalidUserName = errors.New("userName must be a valid email address (3-254 characters)")

type WorkerEnv struct {
	Key   string `json:"key"`
	Value string `json:"value"`
}

type Worker struct {
	Name    string      `json:"name"`
	Image   string      `json:"image"`
	SdkType string      `json:"sdkType"`
	Envs    []WorkerEnv `json:"envs,omitempty"`
}

type ValkeyLimit struct {
	CPU     int `json:"cpu"`     // in millicores, e.g. "500"
	Memory  int `json:"memory"`  // in Gi
	Storage int `json:"storage"` // in Gi
}

type Valkey struct {
	Enabled           bool `json:"enabled"`
	PersistentStorage struct {
		Enabled bool `json:"enabled"`
		Size    int  `json:"size,omitempty"` // in Gi
	} `json:"persistentStorage"`
	Limit ValkeyLimit `json:"limit,omitempty"`
}

type Logs struct {
	Enabled         bool `json:"enabled"`
	GrafanaEnabled  bool `json:"grafanaEnabled"`
	RetentionPeriod int  `json:"retentionPeriod,omitempty"` // in hours
	LogsStorageSize int  `json:"logsStorageSize,omitempty"` // in Gi
}

type Applinth struct {
	Enabled bool `json:"enabled"`
}

type ResourceLimits struct {
	Enabled          bool   `json:"enabled"`
	Cpu              string `json:"cpu"`    // in millicores, e.g. "500"
	Memory           string `json:"memory"` // in Gi
	TopologySlots    int    `json:"topologySlots"`
	Messages         int    `json:"messages"`
	StorageGb        int    `json:"storageGb"`
	TrashDuplication int    `json:"trashDuplication"`
}

type Features struct {
	TraceAuditing        bool `json:"traceAuditing,omitempty"`
	EnterpriseDashboards bool `json:"enterpriseDashboards,omitempty"`
	AuditLogs            bool `json:"auditLogs,omitempty"`
	Pulse                bool `json:"pulse,omitempty"`
}

type Customizations struct {
	Workers        []Worker       `json:"workers,omitempty"`
	Valkey         Valkey         `json:"valkey,omitempty"`
	Logs           Logs           `json:"logs,omitempty"`
	Applinth       Applinth       `json:"applinth,omitempty"`
	ResourceLimits ResourceLimits `json:"resourceLimits,omitempty"`
	Features       Features       `json:"features,omitempty"`
	UserName       string         `json:"userName,omitempty"`
}

type RequestInstanceInfo struct {
	InstanceDisplayName string `json:"instanceDisplayName"`
	InstanceUrlPrefix   string `json:"instanceUrlPrefix"`
	ForceInstanceId     string `json:"forceInstanceId,omitempty"`
}

type RequestInstanceCredentials struct {
	InstanceId     string `json:"instanceId"`
	InstanceSecret string `json:"instanceSecret"`
}

type ExistingInstanceData struct {
	Instance            string
	InstanceDisplayName string
	InstanceUrlPrefix   string
	UserName            string
	UserPassword        string
	MongoPassword       string
	RabbitPassword      string
	BackendJwtKey       string
	CryptSecret         string
	OrchestyApiKey      string
	CloudInstanceId     string
	CloudInstanceSecret string
	S3AccessKey         string
	S3SecretKey         string
	GrafanaPassword     string
	ApplinthPrivateKey  string
	ApplinthPublicKey   string
	Customizations      Customizations
}

// InstanceDTO holds all generated credentials and identifiers for a new instance.
type InstanceDTO struct {
	Instance            string
	InstanceId          string
	InstanceDisplayName string
	InstanceUrlPrefix   string
	UserName            string
	MongoPassword       string
	RabbitPassword      string
	UserPassword        string
	BackendJwtKey       string
	CryptSecret         string
	OrchestyApiKey      string
	CloudInstanceId     string
	CloudInstanceSecret string
	S3AccessKey         string
	S3SecretKey         string
	GrafanaPassword     string
	ApplinthPrivateKey  string
	ApplinthPublicKey   string
	Customizations      Customizations
}

// InstanceInfo is the public response returned after instance creation.
type InstanceInfo struct {
	Instance            string `json:"instance"`
	InstanceDisplayName string `json:"instanceDisplayName"`
	InstanceUrlPrefix   string `json:"instanceUrlPrefix"`
	UserName            string `json:"userName"`
	UserPassword        string `json:"userPassword"`
	GrafanaPassword     string `json:"grafanaPassword"`
	ApplinthPublicKey   string `json:"applinthPublicKey"`
}

// NewInstanceDTO creates a new InstanceDTO with generated credentials.
func NewInstanceDTO(instanceInfo RequestInstanceInfo, instanceCredentials RequestInstanceCredentials, customizations Customizations) (*InstanceDTO, error) {
	instanceDisplayName := strings.TrimSpace(instanceInfo.InstanceDisplayName)
	if instanceDisplayName == "" {
		return nil, fmt.Errorf("instanceDisplayName is empty")
	}

	instanceUrlPrefix := strings.TrimSpace(instanceInfo.InstanceUrlPrefix)
	if instanceUrlPrefix == "" {
		return nil, fmt.Errorf("instanceUrlPrefix is empty")
	}
	if len(instanceUrlPrefix) > maxInstanceURLPrefixLength {
		instanceUrlPrefix = instanceUrlPrefix[:maxInstanceURLPrefixLength]
	}

	forceInstanceId := strings.TrimSpace(instanceInfo.ForceInstanceId)

	var instanceId string
	var err error
	if forceInstanceId != "" {
		instanceId = forceInstanceId
	} else {
		instanceId, err = generatePassword(10, true)
		if err != nil {
			return nil, fmt.Errorf("failed to generate instance prefix: %w", err)
		}
	}

	mongoPwd, err := generatePassword(16, false)
	if err != nil {
		return nil, fmt.Errorf("failed to generate mongo password: %w", err)
	}

	rabbitPwd, err := generatePassword(16, false)
	if err != nil {
		return nil, fmt.Errorf("failed to generate rabbit password: %w", err)
	}

	userPwd, err := generatePassword(16, false)
	if err != nil {
		return nil, fmt.Errorf("failed to generate user password: %w", err)
	}

	backendKey, err := generatePassword(64, false)
	if err != nil {
		return nil, fmt.Errorf("failed to generate backend JWT key: %w", err)
	}

	cryptSec, err := generatePassword(64, false)
	if err != nil {
		return nil, fmt.Errorf("failed to generate crypt secret: %w", err)
	}

	apiKey, err := generatePassword(64, false)
	if err != nil {
		return nil, fmt.Errorf("failed to generate API key: %w", err)
	}

	var grafanaPwd string
	if customizations.Logs.GrafanaEnabled {
		grafanaPwd, err = generatePassword(16, false)
		if err != nil {
			return nil, fmt.Errorf("failed to generate grafana password: %w", err)
		}
	}

	var applinthPrivKey, applinthPubKey string
	if customizations.Applinth.Enabled {
		applinthPrivKey, applinthPubKey, err = generateECKeyPair()
		if err != nil {
			return nil, fmt.Errorf("failed to generate applinth EC key pair: %w", err)
		}
	}

	userName := strings.TrimSpace(customizations.UserName)
	if userName == "" {
		userName = defaultUserName
	} else if len(userName) < 3 || len(userName) > 254 || !emailRegex.MatchString(userName) {
		return nil, ErrInvalidUserName
	}

	return &InstanceDTO{
		Instance:            InstancePrefix + instanceId,
		InstanceId:          instanceId,
		InstanceDisplayName: instanceDisplayName,
		InstanceUrlPrefix:   instanceUrlPrefix,
		UserName:            userName,
		MongoPassword:       mongoPwd,
		RabbitPassword:      rabbitPwd,
		UserPassword:        userPwd,
		BackendJwtKey:       backendKey,
		CryptSecret:         cryptSec,
		OrchestyApiKey:      apiKey,
		CloudInstanceId:     instanceCredentials.InstanceId,
		CloudInstanceSecret: instanceCredentials.InstanceSecret,
		GrafanaPassword:     grafanaPwd,
		ApplinthPrivateKey:  applinthPrivKey,
		ApplinthPublicKey:   applinthPubKey,
		Customizations:      customizations,
	}, nil
}

func NewInstanceDTOFromExistingData(data ExistingInstanceData) (*InstanceDTO, error) {
	instance := strings.TrimSpace(data.Instance)
	if instance == "" {
		return nil, errors.New("instance is required")
	}

	displayName := strings.TrimSpace(data.InstanceDisplayName)
	if displayName == "" {
		return nil, errors.New("instanceDisplayName is required")
	}

	mongoPassword := strings.TrimSpace(data.MongoPassword)
	if mongoPassword == "" {
		return nil, errors.New("mongoPassword is required")
	}

	rabbitPassword := strings.TrimSpace(data.RabbitPassword)
	if rabbitPassword == "" {
		return nil, errors.New("rabbitPassword is required")
	}

	backendJwtKey := strings.TrimSpace(data.BackendJwtKey)
	if backendJwtKey == "" {
		return nil, errors.New("backendJwtKey is required")
	}

	cryptSecret := strings.TrimSpace(data.CryptSecret)
	if cryptSecret == "" {
		return nil, errors.New("cryptSecret is required")
	}

	orchestyApiKey := strings.TrimSpace(data.OrchestyApiKey)
	if orchestyApiKey == "" {
		return nil, errors.New("orchestyApiKey is required")
	}

	return &InstanceDTO{
		Instance:            instance,
		InstanceId:          strings.TrimPrefix(instance, InstancePrefix),
		InstanceDisplayName: displayName,
		InstanceUrlPrefix:   data.InstanceUrlPrefix,
		UserName:            strings.TrimSpace(data.UserName),
		UserPassword:        strings.TrimSpace(data.UserPassword),
		MongoPassword:       mongoPassword,
		RabbitPassword:      rabbitPassword,
		BackendJwtKey:       backendJwtKey,
		CryptSecret:         cryptSecret,
		OrchestyApiKey:      orchestyApiKey,
		CloudInstanceId:     data.CloudInstanceId,
		CloudInstanceSecret: data.CloudInstanceSecret,
		S3AccessKey:         data.S3AccessKey,
		S3SecretKey:         data.S3SecretKey,
		GrafanaPassword:     data.GrafanaPassword,
		ApplinthPrivateKey:  data.ApplinthPrivateKey,
		ApplinthPublicKey:   data.ApplinthPublicKey,
		Customizations:      data.Customizations,
	}, nil
}

func (d *InstanceDTO) ToInstanceInfo(withCredentials bool) InstanceInfo {
	info := InstanceInfo{
		Instance:            d.InstanceId,
		InstanceUrlPrefix:   d.InstanceUrlPrefix,
		InstanceDisplayName: d.InstanceDisplayName,
	}

	if withCredentials {
		info.UserName = d.UserName
		info.UserPassword = d.UserPassword
		info.GrafanaPassword = d.GrafanaPassword
		info.ApplinthPublicKey = d.ApplinthPublicKey
	}

	return info
}

func generatePassword(length int, lowCase bool) (string, error) {
	cs := charsetFull
	if lowCase {
		cs = charsetLower
	}

	result := make([]byte, length)
	for i := range result {
		n, err := rand.Int(rand.Reader, big.NewInt(int64(len(cs))))
		if err != nil {
			return "", fmt.Errorf("failed to generate random number: %w", err)
		}
		result[i] = cs[n.Int64()]
	}

	return string(result), nil
}

func generateECKeyPair() (privateKeyPEM, publicKeyPEM string, err error) {
	privateKey, err := ecdsa.GenerateKey(elliptic.P521(), rand.Reader)
	if err != nil {
		return "", "", fmt.Errorf("generate EC key: %w", err)
	}

	privKeyBytes, err := x509.MarshalECPrivateKey(privateKey)
	if err != nil {
		return "", "", fmt.Errorf("marshal EC private key: %w", err)
	}

	pubKeyBytes, err := x509.MarshalPKIXPublicKey(&privateKey.PublicKey)
	if err != nil {
		return "", "", fmt.Errorf("marshal EC public key: %w", err)
	}

	privPEM := pem.EncodeToMemory(&pem.Block{Type: "EC PRIVATE KEY", Bytes: privKeyBytes})
	pubPEM := pem.EncodeToMemory(&pem.Block{Type: "PUBLIC KEY", Bytes: pubKeyBytes})

	return string(privPEM), string(pubPEM), nil
}
