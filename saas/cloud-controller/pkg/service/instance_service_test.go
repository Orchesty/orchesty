package service

import (
	"errors"
	"strings"
	"testing"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
	"cloud-controller/pkg/objectStorage"

	"go.mongodb.org/mongo-driver/v2/bson"
)

type mongoStub struct {
	createCalls       int
	deleteCalls       int
	dropDatabaseCalls []string
	createErr         error
	deleteErr         error
	dropDatabaseErr   error
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

func (m *mongoStub) DropDatabase(dbName string) error {
	m.dropDatabaseCalls = append(m.dropDatabaseCalls, dbName)
	return m.dropDatabaseErr
}

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

func (k *kubernetesStub) IsNamespaceAvailable(dto *models.InstanceDTO) (bool, error) {
	k.steps = append(k.steps, "is-namespace-available")
	k.lastAppliedDTO = dto
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

func (k *kubernetesStub) GetDeploymentNames(instance string) ([]string, error) {
	k.steps = append(k.steps, "get-deployment-names")
	if err := k.stepErrs["get-deployment-names"]; err != nil {
		return nil, err
	}
	// Return empty list by default for tests - can be customized if needed
	return []string{}, nil
}

func (k *kubernetesStub) ScaleDeploymentsToZero(instance string, components []string) error {
	k.steps = append(k.steps, "scale-deployments-to-zero")
	return k.stepErrs["scale-deployments-to-zero"]
}

func (k *kubernetesStub) ScaleDeploymentsToReplicas(instance string, replicas map[string]int32) error {
	k.steps = append(k.steps, "scale-deployments-to-replicas")
	return k.stepErrs["scale-deployments-to-replicas"]
}

type ingressStub struct {
	steps    []string
	stepErrs map[string]error
}

func (i *ingressStub) RegisterServices(*models.InstanceDTO) error {
	i.steps = append(i.steps, "register-services")
	return i.stepErrs["register-services"]
}

func (i *ingressStub) UpdateServices(*models.InstanceDTO) error {
	i.steps = append(i.steps, "update-services")
	return i.stepErrs["update-services"]
}

func (i *ingressStub) DeleteServices(string) error {
	i.steps = append(i.steps, "delete-services")
	return i.stepErrs["delete-services"]
}

func withKongEnabled(t *testing.T) {
	t.Helper()
	original := config.Kong.Enabled
	config.Kong.Enabled = true
	t.Cleanup(func() { config.Kong.Enabled = original })
}

func withKongDisabled(t *testing.T) {
	t.Helper()
	original := config.Kong.Enabled
	config.Kong.Enabled = false
	t.Cleanup(func() { config.Kong.Enabled = original })
}

type objectStorageStub struct {
	steps    []string
	stepErrs map[string]error
	creds    *objectStorage.HMACCredentials
}

func (o *objectStorageStub) CreateBucket(*models.InstanceDTO) (*objectStorage.HMACCredentials, error) {
	o.steps = append(o.steps, "create-bucket")
	if err := o.stepErrs["create-bucket"]; err != nil {
		return nil, err
	}
	return o.creds, nil
}

func (o *objectStorageStub) UpdateBucket(*models.InstanceDTO) (*objectStorage.HMACCredentials, error) {
	o.steps = append(o.steps, "update-bucket")
	if err := o.stepErrs["update-bucket"]; err != nil {
		return nil, err
	}
	return o.creds, nil
}

func (o *objectStorageStub) DeleteBucket(string) error {
	o.steps = append(o.steps, "delete-bucket")
	return o.stepErrs["delete-bucket"]
}

func (o *objectStorageStub) DeleteHMACKey(string) error {
	o.steps = append(o.steps, "delete-hmac-key")
	return o.stepErrs["delete-hmac-key"]
}

func withGCSEnabled(t *testing.T) {
	t.Helper()
	original := config.GCS.Enabled
	config.GCS.Enabled = true
	t.Cleanup(func() { config.GCS.Enabled = original })
}

func withGCSDisabled(t *testing.T) {
	t.Helper()
	original := config.GCS.Enabled
	config.GCS.Enabled = false
	t.Cleanup(func() { config.GCS.Enabled = original })
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
	service := NewInstanceService(mongo, rabbit, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

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
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

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
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	_, err := service.UpdateInstance(UpdateInstanceRequest{Instance: "instance-test", Customizations: &customizations})
	if err == nil || !strings.Contains(err.Error(), "install helm release") {
		t.Fatalf("expected install helm release error, got %v", err)
	}
}

func TestUpdateInstanceRequiresInstance(t *testing.T) {
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, &kubernetesStub{stepErrs: map[string]error{}}, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

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
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

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
	service := NewInstanceService(mongo, rabbit, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	result, err := service.CreateInstance(CreateInstanceRequest{InstanceInfo: models.RequestInstanceInfo{InstanceDisplayName: "Test instance", InstanceUrlPrefix: "test-instance"}})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if result.Instance == "" {
		t.Fatal("expected generated instance")
	}
	if result.UserName != "orchesty@hanaboso.com" {
		t.Fatalf("expected default username %q, got %q", "orchesty@hanaboso.com", result.UserName)
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
	service := NewInstanceService(mongo, rabbit, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	_, err := service.CreateInstance(CreateInstanceRequest{InstanceInfo: models.RequestInstanceInfo{InstanceDisplayName: "Test instance", InstanceUrlPrefix: "test-instance"}})
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
	withGCSEnabled(t)
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{stepErrs: map[string]error{}, loadedDTO: &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Test",
		MongoPassword:       "mongo-pass",
		RabbitPassword:      "rabbit-pass",
		BackendJwtKey:       "backend-key",
		CryptSecret:         "crypt-secret",
		OrchestyApiKey:      "api-key",
		S3AccessKey:         "GOOG1ETEST",
	}}
	objStorage := &objectStorageStub{stepErrs: map[string]error{}}
	service := NewInstanceService(mongo, rabbit, kubernetes, &ingressStub{stepErrs: map[string]error{}}, objStorage)

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
	if kubernetes.steps[0] != "load-instance-dto" || kubernetes.steps[1] != "delete-namespace" {
		t.Fatalf("expected load-instance-dto then delete-namespace, got %v", kubernetes.steps)
	}
	if len(objStorage.steps) != 2 {
		t.Fatalf("expected two object storage steps, got %v", objStorage.steps)
	}
	if objStorage.steps[0] != "delete-hmac-key" || objStorage.steps[1] != "delete-bucket" {
		t.Fatalf("expected delete-hmac-key then delete-bucket, got %v", objStorage.steps)
	}
}

func TestDeleteInstanceRequiresValue(t *testing.T) {
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, &kubernetesStub{stepErrs: map[string]error{}}, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	err := service.DeleteInstance(" \t\n ")
	if !errors.Is(err, ErrInstanceRequired) {
		t.Fatalf("expected ErrInstanceRequired, got %v", err)
	}
}

func TestDeleteInstanceAggregatesErrors(t *testing.T) {
	withGCSEnabled(t)
	mongo := &mongoStub{deleteErr: errors.New("mongo failed")}
	rabbit := &rabbitStub{stepErrs: map[string]error{
		"delete-user":  errors.New("rabbit user failed"),
		"delete-vhost": errors.New("rabbit vhost failed"),
	}}
	kubernetes := &kubernetesStub{
		stepErrs: map[string]error{
			"delete-namespace": errors.New("k8s failed"),
		},
		loadedDTO: &models.InstanceDTO{
			Instance:            "instance-test",
			InstanceDisplayName: "Test",
			MongoPassword:       "mongo-pass",
			RabbitPassword:      "rabbit-pass",
			BackendJwtKey:       "backend-key",
			CryptSecret:         "crypt-secret",
			OrchestyApiKey:      "api-key",
		},
	}
	service := NewInstanceService(mongo, rabbit, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

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
	service := NewInstanceService(mongo, rabbit, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	_, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo:   models.RequestInstanceInfo{InstanceDisplayName: "Test instance", InstanceUrlPrefix: "test-instance"},
		Customizations: models.Customizations{UserName: "a@b"},
	})
	if !errors.Is(err, ErrInvalidUserName) {
		t.Fatalf("expected ErrInvalidUserName, got %v", err)
	}
}

func TestCreateInstanceInvalidUserNameNotEmail(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(mongo, rabbit, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	_, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo:   models.RequestInstanceInfo{InstanceDisplayName: "Test instance", InstanceUrlPrefix: "test-instance"},
		Customizations: models.Customizations{UserName: "invalid-user"},
	})
	if !errors.Is(err, ErrInvalidUserName) {
		t.Fatalf("expected ErrInvalidUserName, got %v", err)
	}
}

func TestDeleteInstanceDropsDatabases(t *testing.T) {
	withGCSDisabled(t)
	withKongDisabled(t)
	mongo := &mongoStub{}
	service := NewInstanceService(mongo, &rabbitStub{stepErrs: map[string]error{}}, &kubernetesStub{stepErrs: map[string]error{}}, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	if err := service.DeleteInstance("my-instance"); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(mongo.dropDatabaseCalls) != 2 {
		t.Fatalf("expected two drop database calls, got %v", mongo.dropDatabaseCalls)
	}
	if mongo.dropDatabaseCalls[0] != "my-instance" {
		t.Fatalf("expected first drop for 'my-instance', got %q", mongo.dropDatabaseCalls[0])
	}
	if mongo.dropDatabaseCalls[1] != "my-instance-metrics" {
		t.Fatalf("expected second drop for 'my-instance-metrics', got %q", mongo.dropDatabaseCalls[1])
	}
}

func TestDeleteInstanceDropDatabaseError(t *testing.T) {
	withGCSDisabled(t)
	withKongDisabled(t)
	mongo := &mongoStub{dropDatabaseErr: errors.New("drop failed")}
	service := NewInstanceService(mongo, &rabbitStub{stepErrs: map[string]error{}}, &kubernetesStub{stepErrs: map[string]error{}}, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	err := service.DeleteInstance("my-instance")
	if err == nil {
		t.Fatal("expected error")
	}

	message := err.Error()
	if !strings.Contains(message, "drop mongodb database: drop failed") {
		t.Fatalf("expected drop mongodb database error, got %q", message)
	}
	if !strings.Contains(message, "drop mongodb metrics database: drop failed") {
		t.Fatalf("expected drop mongodb metrics database error, got %q", message)
	}
}

func TestCreateInstanceValidCustomEmail(t *testing.T) {
	mongo := &mongoStub{}
	rabbit := &rabbitStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(mongo, rabbit, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	result, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo:   models.RequestInstanceInfo{InstanceDisplayName: "Test instance", InstanceUrlPrefix: "test-instance"},
		Customizations: models.Customizations{UserName: "admin@example.com"},
	})
	if err != nil {
		t.Fatalf("expected no error for valid email, got %v", err)
	}
	if result.UserName != "admin@example.com" {
		t.Fatalf("expected custom username preserved, got %q", result.UserName)
	}
}

func TestCreateInstanceWithKongEnabled(t *testing.T) {
	withKongEnabled(t)
	ingress := &ingressStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, ingress, &objectStorageStub{stepErrs: map[string]error{}})

	_, err := service.CreateInstance(CreateInstanceRequest{InstanceInfo: models.RequestInstanceInfo{InstanceDisplayName: "Test", InstanceUrlPrefix: "test"}})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(ingress.steps) != 1 || ingress.steps[0] != "register-services" {
		t.Fatalf("expected register-services call, got %v", ingress.steps)
	}
}

func TestCreateInstanceWithGCSEnabled(t *testing.T) {
	withGCSEnabled(t)
	withKongDisabled(t)
	objStorage := &objectStorageStub{
		stepErrs: map[string]error{},
		creds:    &objectStorage.HMACCredentials{AccessKey: "ak", SecretKey: "sk"},
	}
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, objStorage)

	_, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo:   models.RequestInstanceInfo{InstanceDisplayName: "Test", InstanceUrlPrefix: "test"},
		Customizations: models.Customizations{Logs: models.Logs{Enabled: true}},
	})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(objStorage.steps) != 1 || objStorage.steps[0] != "create-bucket" {
		t.Fatalf("expected create-bucket call, got %v", objStorage.steps)
	}
}

func TestCreateInstanceRollbackWithKongAndGCS(t *testing.T) {
	withKongEnabled(t)
	withGCSEnabled(t)
	ingress := &ingressStub{stepErrs: map[string]error{}}
	objStorage := &objectStorageStub{
		stepErrs: map[string]error{},
		creds:    &objectStorage.HMACCredentials{AccessKey: "ak", SecretKey: "sk"},
	}
	kubernetes := &kubernetesStub{
		namespaceAvailable: true,
		stepErrs:           map[string]error{"install": errors.New("helm failed")},
	}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, ingress, objStorage)

	_, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo:   models.RequestInstanceInfo{InstanceDisplayName: "Test", InstanceUrlPrefix: "test"},
		Customizations: models.Customizations{Logs: models.Logs{Enabled: true}},
	})
	if err == nil {
		t.Fatal("expected error")
	}

	// Bucket should be rolled back (created before install which fails)
	hasDeleteBucket := false
	hasDeleteHMAC := false
	for _, s := range objStorage.steps {
		if s == "delete-bucket" {
			hasDeleteBucket = true
		}
		if s == "delete-hmac-key" {
			hasDeleteHMAC = true
		}
	}
	if !hasDeleteBucket || !hasDeleteHMAC {
		t.Fatalf("expected bucket and hmac rollback, got %v", objStorage.steps)
	}

	// Kong should NOT be rolled back (install fails before kong registration)
	for _, s := range ingress.steps {
		if s == "delete-services" {
			t.Fatalf("did not expect kong rollback since registration happens after install")
		}
	}
}

func TestDeleteInstanceWithKongEnabled(t *testing.T) {
	withKongEnabled(t)
	withGCSDisabled(t)
	ingress := &ingressStub{stepErrs: map[string]error{}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, &kubernetesStub{stepErrs: map[string]error{}}, ingress, &objectStorageStub{stepErrs: map[string]error{}})

	if err := service.DeleteInstance("instance-test"); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(ingress.steps) != 1 || ingress.steps[0] != "delete-services" {
		t.Fatalf("expected delete-services call, got %v", ingress.steps)
	}
}

func TestUpdateInstanceWithKongEnabled(t *testing.T) {
	withKongEnabled(t)
	withGCSDisabled(t)
	ingress := &ingressStub{stepErrs: map[string]error{}}
	kubernetes := &kubernetesStub{stepErrs: map[string]error{}, loadedDTO: &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Old Name",
		MongoPassword:       "mongo-pass",
		RabbitPassword:      "rabbit-pass",
		BackendJwtKey:       "backend-key",
		CryptSecret:         "crypt-secret",
		OrchestyApiKey:      "api-key",
	}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, ingress, &objectStorageStub{stepErrs: map[string]error{}})

	newName := "New Name"
	_, err := service.UpdateInstance(UpdateInstanceRequest{Instance: "instance-test", InstanceDisplayName: &newName})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(ingress.steps) != 1 || ingress.steps[0] != "update-services" {
		t.Fatalf("expected update-services call, got %v", ingress.steps)
	}
}

func TestUpdateInstanceWithGCSEnabled(t *testing.T) {
	withGCSEnabled(t)
	withKongDisabled(t)
	objStorage := &objectStorageStub{
		stepErrs: map[string]error{},
		creds:    &objectStorage.HMACCredentials{AccessKey: "new-ak", SecretKey: "new-sk"},
	}
	kubernetes := &kubernetesStub{stepErrs: map[string]error{}, loadedDTO: &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Old Name",
		MongoPassword:       "mongo-pass",
		RabbitPassword:      "rabbit-pass",
		BackendJwtKey:       "backend-key",
		CryptSecret:         "crypt-secret",
		OrchestyApiKey:      "api-key",
	}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, objStorage)

	customizations := models.Customizations{Logs: models.Logs{Enabled: true}}
	_, err := service.UpdateInstance(UpdateInstanceRequest{Instance: "instance-test", Customizations: &customizations})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(objStorage.steps) != 1 || objStorage.steps[0] != "update-bucket" {
		t.Fatalf("expected update-bucket call, got %v", objStorage.steps)
	}
}

func TestCreateInstanceWithGrafanaEnabled(t *testing.T) {
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	result, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo:   models.RequestInstanceInfo{InstanceDisplayName: "Test", InstanceUrlPrefix: "test"},
		Customizations: models.Customizations{Logs: models.Logs{GrafanaEnabled: true}},
	})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if result.GrafanaPassword == "" {
		t.Fatal("expected grafana password to be generated")
	}
}

func TestCreateInstanceWithApplinthEnabled(t *testing.T) {
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	result, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo:   models.RequestInstanceInfo{InstanceDisplayName: "Test", InstanceUrlPrefix: "test"},
		Customizations: models.Customizations{Applinth: models.Applinth{Enabled: true}},
	})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if result.ApplinthPublicKey == "" {
		t.Fatal("expected applinth public key to be generated")
	}
	if !strings.Contains(result.ApplinthPublicKey, "BEGIN PUBLIC KEY") {
		t.Fatalf("expected PEM-encoded public key, got %q", result.ApplinthPublicKey)
	}
}

func TestCreateInstanceWithForceInstanceId(t *testing.T) {
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	result, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo: models.RequestInstanceInfo{InstanceDisplayName: "Test", InstanceUrlPrefix: "test", ForceInstanceId: "customid123"},
	})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if result.Instance != "customid123" {
		t.Fatalf("expected instance 'customid123', got %q", result.Instance)
	}
}

func TestCreateInstanceTruncatesInstanceURLPrefix(t *testing.T) {
	kubernetes := &kubernetesStub{namespaceAvailable: true, stepErrs: map[string]error{}}
	service := NewInstanceService(&mongoStub{}, &rabbitStub{stepErrs: map[string]error{}}, kubernetes, &ingressStub{stepErrs: map[string]error{}}, &objectStorageStub{stepErrs: map[string]error{}})

	_, err := service.CreateInstance(CreateInstanceRequest{
		InstanceInfo: models.RequestInstanceInfo{InstanceDisplayName: "Test", InstanceUrlPrefix: "abcdefghijklmnopqrstuvwxyz"},
	})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if kubernetes.lastAppliedDTO == nil {
		t.Fatal("expected dto passed to kubernetes")
	}
	if kubernetes.lastAppliedDTO.InstanceUrlPrefix != "abcdefghijklmnopqrst" {
		t.Fatalf("expected truncated prefix %q, got %q", "abcdefghijklmnopqrst", kubernetes.lastAppliedDTO.InstanceUrlPrefix)
	}
}
