package service

import (
	"errors"
	"strings"
	"testing"

	"cloud-controller/pkg/models"

	"go.mongodb.org/mongo-driver/v2/bson"
)

type mongoStub struct {
	createCalls int
	deleteCalls int
	createErr   error
	deleteErr   error
}

func (m *mongoStub) CreateUser(*models.InstanceDTO) (bson.M, error) {
	m.createCalls++
	if m.createErr != nil {
		return nil, m.createErr
	}
	return bson.M{"ok": 1}, nil
}

func (m *mongoStub) DeleteUser(string) (bson.M, error) {
	m.deleteCalls++
	if m.deleteErr != nil {
		return nil, m.deleteErr
	}
	return bson.M{"ok": 1}, nil
}

func (m *mongoStub) Disconnect() {}

type rabbitStub struct {
	steps    []string
	stepErrs map[string]error
}

func (r *rabbitStub) CreateVHost(*models.InstanceDTO) (bool, error) {
	r.steps = append(r.steps, "create-vhost")
	return true, r.stepErrs["create-vhost"]
}

func (r *rabbitStub) CreateUser(*models.InstanceDTO) (bool, error) {
	r.steps = append(r.steps, "create-user")
	return true, r.stepErrs["create-user"]
}

func (r *rabbitStub) SetPermissions(*models.InstanceDTO) (bool, error) {
	r.steps = append(r.steps, "set-permissions")
	return true, r.stepErrs["set-permissions"]
}

func (r *rabbitStub) DeleteUser(string) (bool, error) {
	r.steps = append(r.steps, "delete-user")
	return true, r.stepErrs["delete-user"]
}

func (r *rabbitStub) DeleteVHost(string) (bool, error) {
	r.steps = append(r.steps, "delete-vhost")
	return true, r.stepErrs["delete-vhost"]
}

type kubernetesStub struct {
	steps              []string
	namespaceAvailable bool
	stepErrs           map[string]error
	loadedDTO          *models.InstanceDTO
	lastAppliedDTO     *models.InstanceDTO
	updatedInstance    string
	updatedDisplayName string
}

func (k *kubernetesStub) CreateNamespace(*models.InstanceDTO) (bool, error) {
	k.steps = append(k.steps, "create-namespace")
	return true, k.stepErrs["create-namespace"]
}

func (k *kubernetesStub) IsNamespaceAvailable(*models.InstanceDTO) (bool, error) {
	k.steps = append(k.steps, "is-namespace-available")
	if err := k.stepErrs["is-namespace-available"]; err != nil {
		return false, err
	}
	return k.namespaceAvailable, nil
}

func (k *kubernetesStub) ApplyDefaultSecret(*models.InstanceDTO) (bool, error) {
	k.steps = append(k.steps, "apply-default-secret")
	return true, k.stepErrs["apply-default-secret"]
}

func (k *kubernetesStub) ApplyInstanceSecret(*models.InstanceDTO) (bool, error) {
	k.steps = append(k.steps, "apply-instance-secret")
	err := k.stepErrs["apply-instance-secret"]
	return err == nil, err
}

func (k *kubernetesStub) LoadInstanceDTO(instance string) (*models.InstanceDTO, error) {
	k.steps = append(k.steps, "load-instance-dto")
	if err := k.stepErrs["load-instance-dto"]; err != nil {
		return nil, err
	}
	if k.loadedDTO == nil {
		return nil, errors.New("missing loaded dto")
	}

	copyDTO := *k.loadedDTO
	copyDTO.Instance = instance

	return &copyDTO, nil
}

func (k *kubernetesStub) UpdateNamespaceDisplayName(instance, displayName string) error {
	k.steps = append(k.steps, "update-namespace-display-name")
	k.updatedInstance = instance
	k.updatedDisplayName = displayName

	return k.stepErrs["update-namespace-display-name"]
}

func (k *kubernetesStub) DeleteNamespace(string) (bool, error) {
	k.steps = append(k.steps, "delete-namespace")
	return true, k.stepErrs["delete-namespace"]
}

func (k *kubernetesStub) Install(*models.InstanceDTO) error {
	k.steps = append(k.steps, "install")
	return k.stepErrs["install"]
}

func TestUpdateInstanceSuccess(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{stepErrs: map[string]error{}, loadedDTO: &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Old Name",
		UserName:            "admin@example.com",
		UserPassword:        "secret",
		MongoPassword:       "mongo-pass",
		RabbitPassword:      "rabbit-pass",
		BackendJwtKey:       "backend-key",
		CryptSecret:         "crypt-secret",
		OrchestyApiKey:      "api-key",
		Customizations: models.Customizations{
			Workers: []models.Worker{
				{
					Name:    "default",
					Image:   "img:v1",
					SdkType: "php",
				},
			},
		},
	}}
	service := NewInstanceService(mongo, rabbit, kubernetes)

	newName := "New Name"
	customizations := models.Customizations{
		Workers: []models.Worker{
			{
				Name:    "default",
				Image:   "img:v2",
				SdkType: "nodejs",
			},
		},
	}

	result, err := service.UpdateInstance(UpdateInstanceRequest{
		Instance:            "instance-test",
		InstanceDisplayName: &newName,
		Customizations:      &customizations,
	})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if result.InstanceDisplayName != "New Name" {
		t.Fatalf("expected updated display name, got %q", result.InstanceDisplayName)
	}
	if kubernetes.updatedInstance != "instance-test" || kubernetes.updatedDisplayName != "New Name" {
		t.Fatalf("unexpected namespace update values %q/%q", kubernetes.updatedInstance, kubernetes.updatedDisplayName)
	}
	if len(kubernetes.steps) != 4 {
		t.Fatalf("expected four kubernetes update steps, got %v", kubernetes.steps)
	}
	if kubernetes.steps[3] != "install" {
		t.Fatalf("expected install as last step, got %v", kubernetes.steps)
	}
}

func TestUpdateInstanceWithoutCustomizationsSkipsInstall(t *testing.T) {
	kubernetes := &kubernetesStub{stepErrs: map[string]error{}, loadedDTO: &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Old Name",
		MongoPassword:       "mongo-pass",
		RabbitPassword:      "rabbit-pass",
		BackendJwtKey:       "backend-key",
		CryptSecret:         "crypt-secret",
		OrchestyApiKey:      "api-key",
	}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes)

	newName := "New Name"
	if _, err := service.UpdateInstance(UpdateInstanceRequest{Instance: "instance-test", InstanceDisplayName: &newName}); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(kubernetes.steps) != 3 {
		t.Fatalf("expected three kubernetes steps without install, got %v", kubernetes.steps)
	}
}

func TestUpdateInstanceReturnsInstallError(t *testing.T) {
	customizations := models.Customizations{
		Workers: []models.Worker{
			{
				Name:    "default",
				Image:   "img:v2",
				SdkType: "nodejs",
			},
		},
	}
	kubernetes := &kubernetesStub{stepErrs: map[string]error{"install": errors.New("helm failed")}, loadedDTO: &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Old Name",
		MongoPassword:       "mongo-pass",
		RabbitPassword:      "rabbit-pass",
		BackendJwtKey:       "backend-key",
		CryptSecret:         "crypt-secret",
		OrchestyApiKey:      "api-key",
	}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes)

	_, err := service.UpdateInstance(UpdateInstanceRequest{Instance: "instance-test", Customizations: &customizations})
	if err == nil || !strings.Contains(err.Error(), "install helm release") {
		t.Fatalf("expected install helm release error, got %v", err)
	}
}

func TestUpdateInstanceRequiresInstance(t *testing.T) {
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, &kubernetesStub{stepErrs: map[string]error{}})

	_, err := service.UpdateInstance(UpdateInstanceRequest{Instance: "  "})
	if !errors.Is(err, ErrInstanceRequired) {
		t.Fatalf("expected ErrInstanceRequired, got %v", err)
	}
}

func TestUpdateInstanceRequiresDisplayNameWhenProvided(t *testing.T) {
	kubernetes := &kubernetesStub{stepErrs: map[string]error{}, loadedDTO: &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Old Name",
		MongoPassword:       "mongo-pass",
		RabbitPassword:      "rabbit-pass",
		BackendJwtKey:       "backend-key",
		CryptSecret:         "crypt-secret",
		OrchestyApiKey:      "api-key",
	}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes)

	emptyName := ""
	_, err := service.UpdateInstance(UpdateInstanceRequest{Instance: "instance-test", InstanceDisplayName: &emptyName})
	if !errors.Is(err, ErrInstanceDisplayNameRequired) {
		t.Fatalf("expected ErrInstanceDisplayNameRequired, got %v", err)
	}
}

func TestCreateInstanceSuccess(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(mongo, rabbit, kubernetes)

	result, err := service.CreateInstance(CreateInstanceRequest{InstanceDisplayName: "Test instance", InstanceUrlPrefix: "test-instance"})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if result.Instance == "" {
		t.Fatal("expected generated instance")
	}
	if result.UserName != defaultUserName {
		t.Fatalf("expected default username %q, got %q", defaultUserName, result.UserName)
	}
	if mongo.createCalls != 1 {
		t.Fatalf("expected one mongo create call, got %d", mongo.createCalls)
	}
	if mongo.deleteCalls != 0 {
		t.Fatalf("expected no mongo rollback, got %d", mongo.deleteCalls)
	}
	if len(rabbit.steps) != 3 {
		t.Fatalf("expected three rabbit steps, got %v", rabbit.steps)
	}
	if len(kubernetes.steps) != 5 {
		t.Fatalf("expected five kubernetes steps, got %v", kubernetes.steps)
	}
}

func TestCreateInstanceRollbackOnInstallError(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{
		namespaceAvailable: true,
		stepErrs:           map[string]error{"install": errors.New("helm failed")},
	}
	service := NewInstanceService(mongo, rabbit, kubernetes)

	_, err := service.CreateInstance(CreateInstanceRequest{InstanceDisplayName: "Test instance", InstanceUrlPrefix: "test-instance"})
	if err == nil {
		t.Fatal("expected error")
	}
	if mongo.deleteCalls != 1 {
		t.Fatalf("expected mongo rollback once, got %d", mongo.deleteCalls)
	}
	if rabbit.steps[len(rabbit.steps)-2] != "delete-user" || rabbit.steps[len(rabbit.steps)-1] != "delete-vhost" {
		t.Fatalf("expected rabbit rollback order, got %v", rabbit.steps)
	}
	if kubernetes.steps[len(kubernetes.steps)-1] != "delete-namespace" {
		t.Fatalf("expected namespace rollback, got %v", kubernetes.steps)
	}
}

func TestDeleteInstanceSuccess(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{stepErrs: map[string]error{}}
	service := NewInstanceService(mongo, rabbit, kubernetes)

	if err := service.DeleteInstance(" instance-test "); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if mongo.deleteCalls != 1 {
		t.Fatalf("expected one mongo delete call, got %d", mongo.deleteCalls)
	}
	if len(rabbit.steps) != 2 {
		t.Fatalf("expected two rabbit delete steps, got %v", rabbit.steps)
	}
	if rabbit.steps[0] != "delete-user" || rabbit.steps[1] != "delete-vhost" {
		t.Fatalf("unexpected rabbit delete order: %v", rabbit.steps)
	}
	if len(kubernetes.steps) != 1 || kubernetes.steps[0] != "delete-namespace" {
		t.Fatalf("expected kubernetes namespace delete, got %v", kubernetes.steps)
	}
}

func TestDeleteInstanceRequiresValue(t *testing.T) {
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, &kubernetesStub{stepErrs: map[string]error{}})

	err := service.DeleteInstance(" \t\n ")
	if !errors.Is(err, ErrInstanceRequired) {
		t.Fatalf("expected ErrInstanceRequired, got %v", err)
	}
}

func TestDeleteInstanceAggregatesErrors(t *testing.T) {
	mongo := &mongoStub{deleteErr: errors.New("mongo failed")}
	rabbit := &rabbitStub{stepErrs: map[string]error{
		"delete-user":  errors.New("rabbit user failed"),
		"delete-vhost": errors.New("rabbit vhost failed"),
	}}
	kubernetes := &kubernetesStub{stepErrs: map[string]error{
		"delete-namespace": errors.New("k8s failed"),
	}}
	service := NewInstanceService(mongo, rabbit, kubernetes)

	err := service.DeleteInstance("instance-test")
	if err == nil {
		t.Fatal("expected error")
	}

	message := err.Error()
	for _, expected := range []string{
		"delete kubernetes namespace: k8s failed",
		"delete rabbitmq user: rabbit user failed",
		"delete rabbitmq vhost: rabbit vhost failed",
		"delete mongodb user: mongo failed",
	} {
		if !strings.Contains(message, expected) {
			t.Fatalf("expected error to contain %q, got %q", expected, message)
		}
	}
}

func TestCreateInstanceInvalidUserNameTooShort(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(mongo, rabbit, kubernetes)

	_, err := service.CreateInstance(CreateInstanceRequest{
		InstanceDisplayName: "Test instance",
		InstanceUrlPrefix:   "test-instance",
		UserName:            "a@b",
	})
	if err != ErrInvalidUserName {
		t.Fatalf("expected ErrInvalidUserName, got %v", err)
	}
}

func TestCreateInstanceInvalidUserNameNotEmail(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(mongo, rabbit, kubernetes)

	_, err := service.CreateInstance(CreateInstanceRequest{
		InstanceDisplayName: "Test instance",
		InstanceUrlPrefix:   "test-instance",
		UserName:            "invalid-user",
	})
	if err != ErrInvalidUserName {
		t.Fatalf("expected ErrInvalidUserName, got %v", err)
	}
}

func TestCreateInstanceValidCustomEmail(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(mongo, rabbit, kubernetes)

	result, err := service.CreateInstance(CreateInstanceRequest{
		InstanceDisplayName: "Test instance",
		InstanceUrlPrefix:   "test-instance",
		UserName:            "admin@example.com",
	})
	if err != nil {
		t.Fatalf("expected no error for valid email, got %v", err)
	}
	if result.UserName != "admin@example.com" {
		t.Fatalf("expected custom username preserved, got %q", result.UserName)
	}
}
