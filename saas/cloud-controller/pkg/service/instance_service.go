package service

import (
	"errors"
	"fmt"
	"strings"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
	"cloud-controller/pkg/mongodb"
	"cloud-controller/pkg/objectStorage"

	"go.mongodb.org/mongo-driver/v2/bson"
)

var (
	ErrInstanceDisplayNameRequired = errors.New("instanceDisplayName is empty")
	ErrInstanceRequired            = errors.New("instance is empty")
	ErrInstanceUnavailable         = errors.New("instance namespace is not available")
	ErrInvalidUserName             = models.ErrInvalidUserName
)

type InputError struct {
	Err error
}

func (e *InputError) Error() string { return e.Err.Error() }
func (e *InputError) Unwrap() error { return e.Err }

type MongoClient interface {
	CreateUser(dto *models.InstanceDTO) (bson.M, error)
	DeleteUser(userName string) (bson.M, error)
	DropDatabase(dbName string) error
	Disconnect()
}

type RabbitClient interface {
	CreateVHost(dto *models.InstanceDTO) (bool, error)
	CreateUser(dto *models.InstanceDTO) (bool, error)
	SetPermissions(dto *models.InstanceDTO) (bool, error)
	DeleteUser(instance string) (bool, error)
	DeleteVHost(instance string) (bool, error)
}

type KubernetesClient interface {
	CreateNamespace(dto *models.InstanceDTO) (bool, error)
	IsNamespaceAvailable(dto *models.InstanceDTO) (bool, error)
	ApplyDefaultSecret(dto *models.InstanceDTO) (bool, error)
	ApplyInstanceSecret(dto *models.InstanceDTO) (bool, error)
	LoadInstanceDTO(instance string) (*models.InstanceDTO, error)
	UpdateNamespaceDisplayName(instance, displayName string) error
	DeleteNamespace(instance string) (bool, error)
	Install(dto *models.InstanceDTO) error
}

type IngressClient interface {
	RegisterServices(dto *models.InstanceDTO) error
	UpdateServices(dto *models.InstanceDTO) error
	DeleteServices(instance string) error
}

type ObjectStorageClient interface {
	CreateBucket(dto *models.InstanceDTO) (*objectStorage.HMACCredentials, error)
	UpdateBucket(dto *models.InstanceDTO) (*objectStorage.HMACCredentials, error)
	DeleteBucket(instance string) error
	DeleteHMACKey(accessKeyId string) error
}

type CreateInstanceRequest struct {
	InstanceInfo        models.RequestInstanceInfo        `json:"instanceInfo"`
	InstanceCredentials models.RequestInstanceCredentials `json:"instanceCredentials"`
	Customizations      models.Customizations             `json:"customizations,omitempty"`
}

type UpdateInstanceRequest struct {
	Instance            string                 `json:"instance"`
	InstanceDisplayName *string                `json:"instanceDisplayName"`
	Customizations      *models.Customizations `json:"customizations,omitempty"`
}

type InstanceService struct {
	mongo         MongoClient
	rabbit        RabbitClient
	kubernetes    KubernetesClient
	ingress       IngressClient
	objectStorage ObjectStorageClient
}

type createState struct {
	mongoUserCreated   bool
	rabbitVHostCreated bool
	rabbitUserCreated  bool
	namespaceCreated   bool
	ingressCreated     bool
	bucketCreated      bool
}

func NewInstanceService(mongo MongoClient, rabbit RabbitClient, kubernetes KubernetesClient, ingress IngressClient, objectStorage ObjectStorageClient) *InstanceService {
	return &InstanceService{
		mongo:         mongo,
		rabbit:        rabbit,
		kubernetes:    kubernetes,
		ingress:       ingress,
		objectStorage: objectStorage,
	}
}

func (s *InstanceService) CreateInstance(request CreateInstanceRequest) (models.InstanceInfo, error) {
	dto, err := models.NewInstanceDTO(request.InstanceInfo, request.InstanceCredentials, request.Customizations)
	if err != nil {
		return models.InstanceInfo{}, &InputError{err}
	}

	available, err := s.kubernetes.IsNamespaceAvailable(dto)
	if err != nil {
		return models.InstanceInfo{}, fmt.Errorf("check namespace availability: %w", err)
	}
	if !available {
		return models.InstanceInfo{}, ErrInstanceUnavailable
	}

	state := createState{}
	if err := s.provision(dto, &state); err != nil {
		return models.InstanceInfo{}, errors.Join(err, s.rollbackCreate(dto, state))
	}

	return dto.ToInstanceInfo(true), nil
}

func (s *InstanceService) DeleteInstance(instance string) error {
	instance = strings.TrimSpace(instance)
	if instance == "" {
		return &InputError{ErrInstanceRequired}
	}

	var errs []error
	var s3AccessKey string

	if config.GCS.Enabled {
		dto, err := s.kubernetes.LoadInstanceDTO(instance)
		if err != nil {
			errs = append(errs, fmt.Errorf("load instance dto for hmac key: %w", err))
		} else {
			s3AccessKey = dto.S3AccessKey
		}
	}

	if _, err := s.kubernetes.DeleteNamespace(instance); err != nil {
		errs = append(errs, fmt.Errorf("delete kubernetes namespace: %w", err))
	}

	if _, err := s.rabbit.DeleteUser(instance); err != nil {
		errs = append(errs, fmt.Errorf("delete rabbitmq user: %w", err))
	}

	if _, err := s.rabbit.DeleteVHost(instance); err != nil {
		errs = append(errs, fmt.Errorf("delete rabbitmq vhost: %w", err))
	}

	if _, err := s.mongo.DeleteUser(instance); err != nil {
		errs = append(errs, fmt.Errorf("delete mongodb user: %w", err))
	}

	if err := s.mongo.DropDatabase(instance); err != nil {
		errs = append(errs, fmt.Errorf("drop mongodb database: %w", err))
	}

	if err := s.mongo.DropDatabase(instance + mongodb.MetricsDbSuffix); err != nil {
		errs = append(errs, fmt.Errorf("drop mongodb metrics database: %w", err))
	}

	if config.Kong.Enabled {
		if err := s.ingress.DeleteServices(instance); err != nil {
			errs = append(errs, fmt.Errorf("delete kong services: %w", err))
		}
	}

	if config.GCS.Enabled {
		if err := s.objectStorage.DeleteHMACKey(s3AccessKey); err != nil {
			errs = append(errs, fmt.Errorf("delete hmac key: %w", err))
		}
		if err := s.objectStorage.DeleteBucket(instance); err != nil {
			errs = append(errs, fmt.Errorf("delete gcs bucket: %w", err))
		}
	}

	return errors.Join(errs...)
}

func (s *InstanceService) UpdateInstance(request UpdateInstanceRequest) (models.InstanceInfo, error) {
	instance := strings.TrimSpace(request.Instance)
	if instance == "" {
		return models.InstanceInfo{}, &InputError{ErrInstanceRequired}
	}

	dto, err := s.kubernetes.LoadInstanceDTO(instance)
	if err != nil {
		return models.InstanceInfo{}, fmt.Errorf("load instance dto: %w", err)
	}

	if request.InstanceDisplayName != nil {
		displayName := strings.TrimSpace(*request.InstanceDisplayName)
		if displayName == "" {
			return models.InstanceInfo{}, &InputError{ErrInstanceDisplayNameRequired}
		}

		dto.InstanceDisplayName = displayName

		if err := s.kubernetes.UpdateNamespaceDisplayName(dto.Instance, dto.InstanceDisplayName); err != nil {
			return models.InstanceInfo{}, fmt.Errorf("update namespace display name: %w", err)
		}
	}

	if request.Customizations != nil {
		customizations, err := models.ProcessCustomizations(*request.Customizations)
		if err != nil {
			return models.InstanceInfo{}, fmt.Errorf("failed to process customizations: %w", err)
		}
		dto.Customizations = customizations
	}

	if config.GCS.Enabled && request.Customizations != nil {
		creds, err := s.objectStorage.UpdateBucket(dto)
		if err != nil {
			return models.InstanceInfo{}, fmt.Errorf("update gcs bucket: %w", err)
		}
		if creds != nil {
			dto.S3AccessKey = creds.AccessKey
			dto.S3SecretKey = creds.SecretKey
		}
	}

	if _, err := s.kubernetes.ApplyInstanceSecret(dto); err != nil {
		return models.InstanceInfo{}, fmt.Errorf("apply instance secret: %w", err)
	}

	if request.Customizations != nil {
		if err := s.kubernetes.Install(dto); err != nil {
			return models.InstanceInfo{}, fmt.Errorf("install helm release: %w", err)
		}
	}

	if config.Kong.Enabled {
		if err := s.ingress.UpdateServices(dto); err != nil {
			return models.InstanceInfo{}, fmt.Errorf("update kong services: %w", err)
		}
	}

	return dto.ToInstanceInfo(false), nil
}

func (s *InstanceService) Shutdown() {
	s.mongo.Disconnect()
}

func (s *InstanceService) provision(dto *models.InstanceDTO, state *createState) error {
	if _, err := s.mongo.CreateUser(dto); err != nil {
		return fmt.Errorf("create mongodb user: %w", err)
	}
	state.mongoUserCreated = true

	if _, err := s.rabbit.CreateVHost(dto); err != nil {
		return fmt.Errorf("create rabbitmq vhost: %w", err)
	}
	state.rabbitVHostCreated = true

	if _, err := s.rabbit.CreateUser(dto); err != nil {
		return fmt.Errorf("create rabbitmq user: %w", err)
	}
	state.rabbitUserCreated = true

	if _, err := s.rabbit.SetPermissions(dto); err != nil {
		return fmt.Errorf("set rabbitmq permissions: %w", err)
	}

	if _, err := s.kubernetes.CreateNamespace(dto); err != nil {
		return fmt.Errorf("create kubernetes namespace: %w", err)
	}
	state.namespaceCreated = true

	if _, err := s.kubernetes.ApplyDefaultSecret(dto); err != nil {
		return fmt.Errorf("apply default secret: %w", err)
	}

	if config.GCS.Enabled && dto.Customizations.Logs.Enabled {
		creds, err := s.objectStorage.CreateBucket(dto)
		if err != nil {
			return fmt.Errorf("create gcs bucket: %w", err)
		}
		if creds != nil {
			dto.S3AccessKey = creds.AccessKey
			dto.S3SecretKey = creds.SecretKey
		}
		state.bucketCreated = true
	}

	if _, err := s.kubernetes.ApplyInstanceSecret(dto); err != nil {
		return fmt.Errorf("apply instance secret: %w", err)
	}

	if err := s.kubernetes.Install(dto); err != nil {
		return fmt.Errorf("install helm release: %w", err)
	}

	if config.Kong.Enabled {
		if err := s.ingress.RegisterServices(dto); err != nil {
			return fmt.Errorf("register kong services: %w", err)
		}
		state.ingressCreated = true
	}

	return nil
}

func (s *InstanceService) rollbackCreate(dto *models.InstanceDTO, state createState) error {
	var errs []error

	if state.bucketCreated {
		if err := s.objectStorage.DeleteHMACKey(dto.S3AccessKey); err != nil {
			errs = append(errs, fmt.Errorf("rollback hmac key: %w", err))
		}
		if err := s.objectStorage.DeleteBucket(dto.Instance); err != nil {
			errs = append(errs, fmt.Errorf("rollback gcs bucket: %w", err))
		}
	}

	if state.ingressCreated {
		if err := s.ingress.DeleteServices(dto.Instance); err != nil {
			errs = append(errs, fmt.Errorf("rollback kong services: %w", err))
		}
	}

	if state.namespaceCreated {
		if _, err := s.kubernetes.DeleteNamespace(dto.Instance); err != nil {
			errs = append(errs, fmt.Errorf("rollback namespace: %w", err))
		}
	}

	if state.rabbitUserCreated {
		if _, err := s.rabbit.DeleteUser(dto.Instance); err != nil {
			errs = append(errs, fmt.Errorf("rollback rabbitmq user: %w", err))
		}
	}

	if state.rabbitVHostCreated {
		if _, err := s.rabbit.DeleteVHost(dto.Instance); err != nil {
			errs = append(errs, fmt.Errorf("rollback rabbitmq vhost: %w", err))
		}
	}

	if state.mongoUserCreated {
		if _, err := s.mongo.DeleteUser(dto.Instance); err != nil {
			errs = append(errs, fmt.Errorf("rollback mongodb user: %w", err))
		}
		if err := s.mongo.DropDatabase(dto.Instance); err != nil {
			errs = append(errs, fmt.Errorf("rollback mongodb database: %w", err))
		}
		if err := s.mongo.DropDatabase(dto.Instance + mongodb.MetricsDbSuffix); err != nil {
			errs = append(errs, fmt.Errorf("rollback mongodb metrics database: %w", err))
		}
	}

	return errors.Join(errs...)
}
