package models

import (
	"crypto/rand"
	"errors"
	"fmt"
	"math/big"
	"strings"
)

const InstancePrefix = "instance-"

const charsetFull = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
const charsetLower = "abcdefghijklmnopqrstuvwxyz0123456789"

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
	CPU     int `json:"cpu"`
	Memory  int `json:"memory"`
	Storage int `json:"storage"`
}

type Valkey struct {
	Enabled           bool `json:"enabled"`
	PersistentStorage struct {
		Enabled bool `json:"enabled"`
		Size    int  `json:"size,omitempty"`
	} `json:"persistentStorage"`
	Limit ValkeyLimit `json:"limit,omitempty"`
}

type Logs struct {
	Enabled        bool `json:"enabled"`
	GrafanaEnabled bool `json:"grafanaEnabled"`
}

type Customizations struct {
	Workers              []Worker `json:"workers,omitempty"`
	Valkey               Valkey   `json:"valkey,omitempty"`
	Logs                 Logs     `json:"logs,omitempty"`
	TraceAuditing        bool     `json:"traceAuditing,omitempty"`
	EnterpriseDashboards bool     `json:"enterpriseDashboards,omitempty"`
	AuditLogs            bool     `json:"auditLogs,omitempty"`
	UseBundle            bool     `json:"useBundle,omitempty"`
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
	Customizations      Customizations
}

// InstanceInfo is the public response returned after instance creation.
type InstanceInfo struct {
	Instance            string `json:"instance"`
	InstanceDisplayName string `json:"instanceDisplayName"`
	InstanceUrlPrefix   string `json:"instanceUrlPrefix"`
	UserName            string `json:"userName"`
	UserPassword        string `json:"userPassword"`
}

// NewInstanceDTO creates a new InstanceDTO with generated credentials.
func NewInstanceDTO(instanceDisplayName, instanceUrlPrefix, userName string, customizations Customizations) (*InstanceDTO, error) {
	instanceId, err := generatePassword(10, true)
	if err != nil {
		return nil, fmt.Errorf("failed to generate instance prefix: %w", err)
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

		// Todo asi přidat i grafanu
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
