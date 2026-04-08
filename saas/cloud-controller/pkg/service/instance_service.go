package service

import (
	"errors"
	"fmt"
	"regexp"
	"strings"

	"cloud-controller/pkg/models"

	"go.mongodb.org/mongo-driver/v2/bson"
)

const defaultUserName = "orchesty@hanaboso.com"

var (
	ErrInstanceDisplayNameRequired = errors.New("instanceDisplayName is empty")
	ErrInstanceUrlPrefixRequired   = errors.New("instanceUrlPrefix is empty")
	ErrInstanceRequired            = errors.New("instance is empty")
	ErrInstanceUnavailable         = errors.New("instance namespace is not available")
	ErrInvalidUserName             = errors.New("userName must be a valid email address (3-254 characters)")

	emailRegex = regexp.MustCompile(`^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$`)
)

type MongoClient interface {
	CreateUser(dto *models.InstanceDTO) (bson.M, error)
	DeleteUser(userName string) (bson.M, error)
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

type CreateInstanceRequest struct {
	InstanceDisplayName string                `json:"instanceDisplayName"`
	UserName            string                `json:"userName"`
	InstanceUrlPrefix   string                `json:"instanceUrlPrefix"`
	Customizations      models.Customizations `json:"customizations"`
}

type UpdateInstanceRequest struct {
	Instance            string                 `json:"instance"`
	InstanceDisplayName *string                `json:"instanceDisplayName,omitempty"`
	Customizations      *models.Customizations `json:"customizations,omitempty"`
}

type InstanceService struct {
	mongo      MongoClient
	rabbit     RabbitClient
	kubernetes KubernetesClient
}

type createState struct {
	mongoUserCreated   bool
	rabbitVHostCreated bool
	rabbitUserCreated  bool
	namespaceCreated   bool
}

func NewInstanceService(mongo MongoClient, rabbit RabbitClient, kubernetes KubernetesClient) *InstanceService {
	return &InstanceService{
		mongo:      mongo,
		rabbit:     rabbit,
		kubernetes: kubernetes,
	}
}

func (s *InstanceService) CreateInstance(request CreateInstanceRequest) (models.InstanceInfo, error) {
	instanceDisplayName := strings.TrimSpace(request.InstanceDisplayName)
	if instanceDisplayName == "" {
		return models.InstanceInfo{}, ErrInstanceDisplayNameRequired
	}

	instanceUrlPrefix := strings.TrimSpace(request.InstanceUrlPrefix)
	if instanceUrlPrefix == "" {
		return models.InstanceInfo{}, ErrInstanceUrlPrefixRequired
	}

	userName := strings.TrimSpace(request.UserName)
	if userName == "" {
		userName = defaultUserName
	} else if len(userName) < 3 || len(userName) > 254 || !emailRegex.MatchString(userName) {
		return models.InstanceInfo{}, ErrInvalidUserName
	}

	dto, err := models.NewInstanceDTO(instanceDisplayName, instanceUrlPrefix, userName, request.Customizations)
	if err != nil {
		return models.InstanceInfo{}, fmt.Errorf("generate instance credentials: %w", err)
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
		return ErrInstanceRequired
	}

	var errs []error

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

	return errors.Join(errs...)
}

func (s *InstanceService) UpdateInstance(request UpdateInstanceRequest) (models.InstanceInfo, error) {
	instance := strings.TrimSpace(request.Instance)
	if instance == "" {
		return models.InstanceInfo{}, ErrInstanceRequired
	}

	dto, err := s.kubernetes.LoadInstanceDTO(instance)
	if err != nil {
		return models.InstanceInfo{}, fmt.Errorf("load instance dto: %w", err)
	}

	if request.InstanceDisplayName != nil {
		displayName := strings.TrimSpace(*request.InstanceDisplayName)
		if displayName == "" {
			return models.InstanceInfo{}, ErrInstanceDisplayNameRequired
		}

		dto.InstanceDisplayName = displayName
	}

	if request.Customizations != nil {
		dto.Customizations = *request.Customizations
	}

	if err := s.kubernetes.UpdateNamespaceDisplayName(dto.Instance, dto.InstanceDisplayName); err != nil {
		return models.InstanceInfo{}, fmt.Errorf("update namespace display name: %w", err)
	}

	if _, err := s.kubernetes.ApplyInstanceSecret(dto); err != nil {
		return models.InstanceInfo{}, fmt.Errorf("apply instance secret: %w", err)
	}

	if request.Customizations != nil {
		if err := s.kubernetes.Install(dto); err != nil {
			return models.InstanceInfo{}, fmt.Errorf("install helm release: %w", err)
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

	if _, err := s.kubernetes.ApplyInstanceSecret(dto); err != nil {
		return fmt.Errorf("apply instance secret: %w", err)
	}

	if err := s.kubernetes.Install(dto); err != nil {
		return fmt.Errorf("install helm release: %w", err)
	}

	return nil
}

func (s *InstanceService) rollbackCreate(dto *models.InstanceDTO, state createState) error {
	var errs []error

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
	}

	return errors.Join(errs...)
}
