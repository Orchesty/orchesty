package kubernetes

import (
	"errors"
	"testing"

	"cloud-controller/pkg/models"

	corev1 "k8s.io/api/core/v1"
	metav1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/client-go/kubernetes/fake"
)

type helmStub struct {
	called bool
	err    error
	dto    *models.InstanceDTO
}

func (h *helmStub) Install(dto *models.InstanceDTO) error {
	h.called = true
	h.dto = dto
	return h.err
}

func testK8sDTO() *models.InstanceDTO {
	return &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Test Instance",
		MongoPassword:       "mongo-pass",
		RabbitPassword:      "rabbit-pass",
		BackendJwtKey:       "jwt-key",
		CryptSecret:         "crypt-secret",
		OrchestyApiKey:      "api-key",
	}
}

func TestCreateNamespace(t *testing.T) {
	client := &Client{clientSet: fake.NewSimpleClientset()}
	dto := testK8sDTO()

	ok, err := client.CreateNamespace(dto)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	namespace, err := client.clientSet.CoreV1().Namespaces().Get(t.Context(), dto.Instance, metav1.GetOptions{})
	if err != nil {
		t.Fatalf("expected namespace to exist, got %v", err)
	}
	if namespace.Labels["oc-instance-displayname"] != dto.InstanceDisplayName {
		t.Fatalf("unexpected label value %q", namespace.Labels["oc-instance-displayname"])
	}
}

func TestIsNamespaceAvailable(t *testing.T) {
	dto := testK8sDTO()
	client := &Client{clientSet: fake.NewSimpleClientset()}

	available, err := client.IsNamespaceAvailable(dto)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !available {
		t.Fatal("expected namespace to be available")
	}

	client = &Client{clientSet: fake.NewSimpleClientset(&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: dto.Instance}})}
	available, err = client.IsNamespaceAvailable(dto)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if available {
		t.Fatal("expected namespace to be unavailable")
	}
}

func TestApplyDefaultSecret(t *testing.T) {
	dto := testK8sDTO()
	client := &Client{clientSet: fake.NewSimpleClientset(
		&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: dto.Instance}},
		&corev1.Secret{
			ObjectMeta: metav1.ObjectMeta{Name: "hanaboso", Namespace: "cloud-control"},
			Data:       map[string][]byte{"foo": []byte("bar")},
		},
	)}

	ok, err := client.ApplyDefaultSecret(dto)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	secret, err := client.clientSet.CoreV1().Secrets(dto.Instance).Get(t.Context(), "hanaboso", metav1.GetOptions{})
	if err != nil {
		t.Fatalf("expected secret to exist, got %v", err)
	}
	if secret.Namespace != dto.Instance {
		t.Fatalf("expected secret namespace %q, got %q", dto.Instance, secret.Namespace)
	}
	if string(secret.Data["foo"]) != "bar" {
		t.Fatalf("unexpected secret data %q", string(secret.Data["foo"]))
	}
}

func TestApplyInstanceSecret(t *testing.T) {
	dto := testK8sDTO()
	client := &Client{clientSet: fake.NewSimpleClientset(&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: dto.Instance}})}

	ok, err := client.ApplyInstanceSecret(dto)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	secret, err := client.clientSet.CoreV1().Secrets(dto.Instance).Get(t.Context(), "orchesty-secrets", metav1.GetOptions{})
	if err != nil {
		t.Fatalf("expected secret to exist, got %v", err)
	}
	if secret.StringData["mongodb_db"] != dto.Instance {
		t.Fatalf("expected mongodb_db %q, got %q", dto.Instance, secret.StringData["mongodb_db"])
	}
	if secret.StringData["metrics_db"] != dto.Instance+"-metrics" {
		t.Fatalf("unexpected metrics_db %q", secret.StringData["metrics_db"])
	}
	if secret.StringData["rabbitmq_dsn"] == "" {
		t.Fatal("expected rabbitmq_dsn to be set")
	}
	if secret.StringData["backend_jwt_key"] != dto.BackendJwtKey {
		t.Fatalf("unexpected backend_jwt_key %q", secret.StringData["backend_jwt_key"])
	}
}

func TestApplyInstanceSecretUpdatesExisting(t *testing.T) {
	dto := testK8sDTO()
	client := &Client{clientSet: fake.NewSimpleClientset(
		&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: dto.Instance}},
		&corev1.Secret{ObjectMeta: metav1.ObjectMeta{Name: "orchesty-secrets", Namespace: dto.Instance}, Data: map[string][]byte{"backend_jwt_key": []byte("old")}},
	)}

	ok, err := client.ApplyInstanceSecret(dto)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	secret, err := client.clientSet.CoreV1().Secrets(dto.Instance).Get(t.Context(), "orchesty-secrets", metav1.GetOptions{})
	if err != nil {
		t.Fatalf("expected secret to exist, got %v", err)
	}
	if string(secret.Data["backend_jwt_key"]) != dto.BackendJwtKey {
		t.Fatalf("expected updated backend_jwt_key %q, got %q", dto.BackendJwtKey, string(secret.Data["backend_jwt_key"]))
	}
	if string(secret.Data["oc_instance_display_name"]) != dto.InstanceDisplayName {
		t.Fatalf("expected oc_instance_display_name %q, got %q", dto.InstanceDisplayName, string(secret.Data["oc_instance_display_name"]))
	}
}

func TestDeleteNamespace(t *testing.T) {
	client := &Client{clientSet: fake.NewSimpleClientset(&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: "instance-test"}})}

	ok, err := client.DeleteNamespace("instance-test")
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	if _, err := client.clientSet.CoreV1().Namespaces().Get(t.Context(), "instance-test", metav1.GetOptions{}); err == nil {
		t.Fatal("expected namespace to be deleted")
	}
}

func TestUpdateNamespaceDisplayName(t *testing.T) {
	client := &Client{clientSet: fake.NewSimpleClientset(
		&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: "instance-test", Labels: map[string]string{"oc-instance-displayname": "Old Name"}}},
	)}

	if err := client.UpdateNamespaceDisplayName("instance-test", "New Name"); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	namespace, err := client.clientSet.CoreV1().Namespaces().Get(t.Context(), "instance-test", metav1.GetOptions{})
	if err != nil {
		t.Fatalf("expected namespace to exist, got %v", err)
	}
	if namespace.Labels["oc-instance-displayname"] != "New Name" {
		t.Fatalf("expected updated label, got %q", namespace.Labels["oc-instance-displayname"])
	}
}

func TestHealth(t *testing.T) {
	client := &Client{clientSet: fake.NewSimpleClientset(&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: "default"}})}

	if err := client.Health(); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
}

func TestInstallDelegatesToHelm(t *testing.T) {
	stub := &helmStub{}
	dto := testK8sDTO()
	client := &Client{helm: stub}

	if err := client.Install(dto); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !stub.called {
		t.Fatal("expected helm Install to be called")
	}
	if stub.dto != dto {
		t.Fatal("expected helm Install to receive the same DTO")
	}

	stub.err = errors.New("install failed")
	if err := client.Install(dto); !errors.Is(err, stub.err) {
		t.Fatalf("expected propagated helm error, got %v", err)
	}
}

func TestLoadInstanceDTOSuccess(t *testing.T) {
	client := &Client{clientSet: fake.NewSimpleClientset(
		&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: "instance-test", Labels: map[string]string{"oc-instance-displayname": "Demo Instance"}}},
		&corev1.Secret{
			ObjectMeta: metav1.ObjectMeta{Name: "orchesty-secrets", Namespace: "instance-test"},
			Data: map[string][]byte{
				"backend_jwt_key":          []byte("backend-key"),
				"crypt_secret":             []byte("crypt-secret"),
				"orchesty_api_key":         []byte("api-key"),
				"mongodb_dsn":              []byte("mongodb://instance-test:mongo-pass@mongos.default.svc.cluster.local/instance-test?authSource=admin"),
				"rabbitmq_dsn":             []byte("amqp://instance-test:rabbit-pass@rabbitmq-proxy.default.svc.cluster.local:5672/instance-test"),
				"oc_user_name":             []byte("admin@example.com"),
				"oc_user_password":         []byte("user-pass"),
				"oc_instance_display_name": []byte("Demo Instance"),
				"oc_instance_url_prefix":   []byte("instance-test"),
			},
		},
	)}

	dto, err := client.LoadInstanceDTO(" instance-test ")
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if dto.Instance != "instance-test" {
		t.Fatalf("unexpected instance %q", dto.Instance)
	}
	if dto.InstanceDisplayName != "Demo Instance" {
		t.Fatalf("unexpected display name %q", dto.InstanceDisplayName)
	}
	if dto.MongoPassword != "mongo-pass" {
		t.Fatalf("unexpected mongo password %q", dto.MongoPassword)
	}
	if dto.RabbitPassword != "rabbit-pass" {
		t.Fatalf("unexpected rabbit password %q", dto.RabbitPassword)
	}
	if dto.UserName != "admin@example.com" {
		t.Fatalf("unexpected user name %q", dto.UserName)
	}
	if dto.UserPassword != "user-pass" {
		t.Fatalf("unexpected user password %q", dto.UserPassword)
	}
}

func TestLoadInstanceDTOInvalidMongoDSN(t *testing.T) {
	client := &Client{clientSet: fake.NewSimpleClientset(
		&corev1.Namespace{ObjectMeta: metav1.ObjectMeta{Name: "instance-test"}},
		&corev1.Secret{
			ObjectMeta: metav1.ObjectMeta{Name: "orchesty-secrets", Namespace: "instance-test"},
			Data: map[string][]byte{
				"backend_jwt_key":  []byte("backend-key"),
				"crypt_secret":     []byte("crypt-secret"),
				"orchesty_api_key": []byte("api-key"),
				"mongodb_dsn":      []byte("invalid"),
				"rabbitmq_dsn":     []byte("amqp://instance-test:rabbit-pass@rabbitmq-proxy.default.svc.cluster.local:5672/instance-test"),
			},
		},
	)}

	if _, err := client.LoadInstanceDTO("instance-test"); err == nil {
		t.Fatal("expected error for invalid mongodb dsn")
	}
}
