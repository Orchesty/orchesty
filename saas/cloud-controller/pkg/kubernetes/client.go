package kubernetes

import (
	"context"
	"errors"
	"fmt"
	"net/url"
	"strings"
	"sync"
	"time"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"

	corev1 "k8s.io/api/core/v1"
	apierrors "k8s.io/apimachinery/pkg/api/errors"
	metav1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/apimachinery/pkg/types"
	"k8s.io/client-go/kubernetes"
	"k8s.io/client-go/rest"
	"k8s.io/client-go/tools/clientcmd"
)

const requestTimeout = 20 * time.Second

const (
	orchestySecretsName       = "orchesty-secrets"
	defaultSecretDisplayLabel = "oc-instance-displayname"
	alloyClusterRoleNameFmt   = "orchesty-alloy-%s"
	grafanaClusterRoleNameFmt = "orchesty-grafana-%s-clusterrole"
	lokiClusterRoleNameFmt    = "orchesty-loki-%s-clusterrole"
	rbacBindingSuffix         = "binding"
	helmReleaseNameAnnotation = "meta.helm.sh/release-name"
	helmReleaseNSAnnotation   = "meta.helm.sh/release-namespace"
)

type Client struct {
	clientSet kubernetes.Interface
	initMu    sync.Mutex
	helm      helmInstaller
}

type helmInstaller interface {
	Install(dto *models.InstanceDTO) error
}

func NewClient() *Client {
	return &Client{
		helm: NewHelm(),
	}
}

func (c *Client) CreateNamespace(dto *models.InstanceDTO) (bool, error) {
	clientSet, err := c.getClientSet()
	if err != nil {
		return false, err
	}

	labelDisplayName, err := sanitizeK8sLabelValue(dto.InstanceDisplayName)
	if err != nil {
		return false, fmt.Errorf("invalid instance display name for namespace label: %w", err)
	}

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	_, err = clientSet.CoreV1().Namespaces().Create(ctx, &corev1.Namespace{
		ObjectMeta: metav1.ObjectMeta{
			Name: dto.Instance,
			Labels: map[string]string{
				"oc-instance-displayname": labelDisplayName,
			},
		},
	}, metav1.CreateOptions{})
	if err != nil {
		return false, err
	}

	return true, nil
}

func (c *Client) IsNamespaceAvailable(dto *models.InstanceDTO) (bool, error) {
	clientSet, err := c.getClientSet()
	if err != nil {
		return false, err
	}

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	_, err = clientSet.CoreV1().Namespaces().Get(ctx, dto.Instance, metav1.GetOptions{})
	if err != nil {
		if apierrors.IsNotFound(err) {
			return true, nil
		}

		return false, err
	}

	return false, nil
}

func (c *Client) ApplyDefaultSecret(dto *models.InstanceDTO) (bool, error) {
	clientSet, err := c.getClientSet()
	if err != nil {
		return false, err
	}

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	secret, err := clientSet.CoreV1().Secrets("cloud-control").Get(ctx, "hanaboso", metav1.GetOptions{})
	if err != nil {
		return false, err
	}

	secret = secret.DeepCopy()
	secret.ObjectMeta = metav1.ObjectMeta{
		Name: secret.Name,
	}

	_, err = clientSet.CoreV1().Secrets(dto.Instance).Create(ctx, secret, metav1.CreateOptions{})
	if err != nil {
		return false, err
	}

	return true, nil
}

func (c *Client) ApplyInstanceSecret(dto *models.InstanceDTO) (bool, error) {
	clientSet, err := c.getClientSet()
	if err != nil {
		return false, err
	}

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	stringData := map[string]string{
		// Basic app configs
		"backend_jwt_key":  dto.BackendJwtKey,
		"crypt_secret":     dto.CryptSecret,
		"orchesty_api_key": dto.OrchestyApiKey,

		// Database credentials
		"mongodb_dsn": fmt.Sprintf(
			"mongodb://%s:%s@%s/%s?authSource=admin&readPreference=primary&replicaSet=rs0",
			dto.Instance,
			dto.MongoPassword,
			config.MongoDB.Hostname,
			dto.Instance,
		),
		"mongodb_db": dto.Instance,
		"metrics_dsn": fmt.Sprintf(
			"mongodb://%s:%s@%s/%s-metrics?authSource=admin&readPreference=primary&replicaSet=rs0",
			dto.Instance,
			dto.MongoPassword,
			config.MongoDB.Hostname,
			dto.Instance,
		),
		"metrics_db": dto.Instance + "-metrics",

		// RabbitMQ credentials
		"rabbitmq_dsn": fmt.Sprintf(
			"amqp://%s:%s@%s:5672/%s",
			dto.Instance,
			dto.RabbitPassword,
			config.RabbitMQ.Hostname,
			dto.Instance,
		),
		"rabbitmq_url":      fmt.Sprintf("http://%s:%s", config.RabbitMQ.Hostname, config.RabbitMQ.ManagementPort),
		"rabbitmq_user":     dto.Instance,
		"rabbitmq_password": dto.RabbitPassword,

		// Instance info
		"oc_instance_display_name": dto.InstanceDisplayName,
		"oc_instance_url_prefix":   dto.InstanceUrlPrefix,
		"oc_user_name":             dto.UserName,
		"oc_user_password":         dto.UserPassword,

		// Customization envs
		"orchesty_cloud_instance_id":     dto.CloudInstanceId,
		"orchesty_cloud_instance_secret": dto.CloudInstanceSecret,

		// Docs search token for backend
		"orchesty_cloud_docs_search_token": config.Orchesty.DocsSearchToken,
	}

	if dto.Customizations.Applinth.Enabled {
		stringData["applinth_jwe_private_key"] = dto.ApplinthPrivateKey
		stringData["applinth_jwe_public_key"] = dto.ApplinthPublicKey
	}

	// Grafana admin user
	if dto.Customizations.Logs.GrafanaEnabled {
		stringData["admin-user"] = "admin"
		stringData["admin-password"] = dto.GrafanaPassword
	}

	// S3 Storage for Loki
	if dto.Customizations.Logs.Enabled {
		stringData["s3-endpoint"] = config.GCS.S3Endpoint()
		stringData["s3-bucket"] = "logs-" + dto.Instance
		stringData["s3-access-key"] = dto.S3AccessKey
		stringData["s3-secret-key"] = dto.S3SecretKey
	}

	secret := &corev1.Secret{
		TypeMeta: metav1.TypeMeta{
			APIVersion: "v1",
			Kind:       "Secret",
		},
		ObjectMeta: metav1.ObjectMeta{
			Name: orchestySecretsName,
		},
		StringData: stringData,
	}

	_, err = clientSet.CoreV1().Secrets(dto.Instance).Create(ctx, secret, metav1.CreateOptions{})
	if err != nil {
		if !apierrors.IsAlreadyExists(err) {
			return false, err
		}

		existing, getErr := clientSet.CoreV1().Secrets(dto.Instance).Get(ctx, orchestySecretsName, metav1.GetOptions{})
		if getErr != nil {
			return false, getErr
		}

		if existing.Data == nil {
			existing.Data = map[string][]byte{}
		}

		for key, value := range stringData {
			existing.Data[key] = []byte(value)
		}

		if _, updateErr := clientSet.CoreV1().Secrets(dto.Instance).Update(ctx, existing, metav1.UpdateOptions{}); updateErr != nil {
			return false, updateErr
		}
	}

	return true, nil
}

func (c *Client) UpdateNamespaceDisplayName(instance, displayName string) error {
	clientSet, err := c.getClientSet()
	if err != nil {
		return err
	}

	labelDisplayName, err := sanitizeK8sLabelValue(displayName)
	if err != nil {
		return fmt.Errorf("invalid instance display name for namespace label: %w", err)
	}

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	namespace, err := clientSet.CoreV1().Namespaces().Get(ctx, instance, metav1.GetOptions{})
	if err != nil {
		return err
	}

	if namespace.Labels == nil {
		namespace.Labels = map[string]string{}
	}
	namespace.Labels[defaultSecretDisplayLabel] = labelDisplayName

	_, err = clientSet.CoreV1().Namespaces().Update(ctx, namespace, metav1.UpdateOptions{})
	return err
}

func (c *Client) LoadInstanceDTO(instance string) (*models.InstanceDTO, error) {
	instance = strings.TrimSpace(instance)
	if instance == "" {
		return nil, fmt.Errorf("instance is required")
	}

	clientSet, err := c.getClientSet()
	if err != nil {
		return nil, err
	}

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	secret, err := clientSet.CoreV1().Secrets(instance).Get(ctx, orchestySecretsName, metav1.GetOptions{})
	if err != nil {
		return nil, fmt.Errorf("get secret %s/%s: %w", instance, orchestySecretsName, err)
	}

	mongoPassword, err := extractMongoPassword(getSecretValue(secret, "mongodb_dsn"))
	if err != nil {
		return nil, fmt.Errorf("extract mongo password: %w", err)
	}

	rabbitPassword, err := extractRabbitPassword(getSecretValue(secret, "rabbitmq_dsn"))
	if err != nil {
		return nil, fmt.Errorf("extract rabbit password: %w", err)
	}

	return models.NewInstanceDTOFromExistingData(models.ExistingInstanceData{
		Instance:            instance,
		MongoPassword:       mongoPassword,
		RabbitPassword:      rabbitPassword,
		InstanceDisplayName: getSecretValue(secret, "oc_instance_display_name"),
		UserName:            getSecretValue(secret, "oc_user_name"),
		UserPassword:        getSecretValue(secret, "oc_user_password"),
		InstanceUrlPrefix:   getSecretValue(secret, "oc_instance_url_prefix"),
		BackendJwtKey:       getSecretValue(secret, "backend_jwt_key"),
		CryptSecret:         getSecretValue(secret, "crypt_secret"),
		OrchestyApiKey:      getSecretValue(secret, "orchesty_api_key"),
		CloudInstanceId:     getSecretValue(secret, "orchesty_cloud_instance_id"),
		CloudInstanceSecret: getSecretValue(secret, "orchesty_cloud_instance_secret"),
		S3AccessKey:         getSecretValue(secret, "s3-access-key"),
		S3SecretKey:         getSecretValue(secret, "s3-secret-key"),
		GrafanaPassword:     getSecretValue(secret, "admin-password"),
		ApplinthPrivateKey:  getSecretValue(secret, "applinth_jwe_private_key"),
		ApplinthPublicKey:   getSecretValue(secret, "applinth_jwe_public_key"),
	})
}

func (c *Client) DeleteNamespace(instance string) (bool, error) {
	clientSet, err := c.getClientSet()
	if err != nil {
		return false, err
	}

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	var deleteErrs []error

	if err = c.deleteAlloyClusterRBAC(ctx, clientSet, instance); err != nil {
		deleteErrs = append(deleteErrs, err)
	}

	err = clientSet.CoreV1().Namespaces().Delete(ctx, instance, metav1.DeleteOptions{})
	if err != nil && !apierrors.IsNotFound(err) {
		deleteErrs = append(deleteErrs, err)
	}

	if len(deleteErrs) > 0 {
		return false, errors.Join(deleteErrs...)
	}

	return true, nil
}

func (c *Client) deleteAlloyClusterRBAC(ctx context.Context, clientSet kubernetes.Interface, instance string) error {
	var deleteErrs []error

	for _, resource := range clusterRBACResources(instance) {
		role, err := clientSet.RbacV1().ClusterRoles().Get(ctx, resource.roleName, metav1.GetOptions{})
		if err != nil {
			if !apierrors.IsNotFound(err) {
				deleteErrs = append(deleteErrs, fmt.Errorf("get clusterrole %s: %w", resource.roleName, err))
			}
		} else if shouldDeleteOwnedHelmResource(role.ObjectMeta, instance) {
			if err = clientSet.RbacV1().ClusterRoles().Delete(ctx, resource.roleName, metav1.DeleteOptions{}); err != nil && !apierrors.IsNotFound(err) {
				deleteErrs = append(deleteErrs, fmt.Errorf("delete clusterrole %s: %w", resource.roleName, err))
			}
		}

		binding, err := clientSet.RbacV1().ClusterRoleBindings().Get(ctx, resource.bindingName, metav1.GetOptions{})
		if err != nil {
			if !apierrors.IsNotFound(err) {
				deleteErrs = append(deleteErrs, fmt.Errorf("get clusterrolebinding %s: %w", resource.bindingName, err))
			}
		} else if shouldDeleteOwnedHelmResource(binding.ObjectMeta, instance) {
			if err = clientSet.RbacV1().ClusterRoleBindings().Delete(ctx, resource.bindingName, metav1.DeleteOptions{}); err != nil && !apierrors.IsNotFound(err) {
				deleteErrs = append(deleteErrs, fmt.Errorf("delete clusterrolebinding %s: %w", resource.bindingName, err))
			}
		}
	}

	if len(deleteErrs) > 0 {
		return errors.Join(deleteErrs...)
	}

	return nil
}

func shouldDeleteOwnedHelmResource(meta metav1.ObjectMeta, instance string) bool {
	releaseNamespace := strings.TrimSpace(meta.Annotations[helmReleaseNSAnnotation])
	releaseName := strings.TrimSpace(meta.Annotations[helmReleaseNameAnnotation])

	if releaseNamespace == "" && releaseName == "" {
		return true
	}

	return releaseNamespace == instance && releaseName == orchestyRepo
}

type clusterRBACResource struct {
	roleName    string
	bindingName string
}

func clusterRBACResources(instance string) []clusterRBACResource {
	instance = strings.TrimSpace(instance)
	if instance == "" {
		return nil
	}

	alloyName := fmt.Sprintf(alloyClusterRoleNameFmt, instance)
	grafanaRole := fmt.Sprintf(grafanaClusterRoleNameFmt, instance)
	lokiRole := fmt.Sprintf(lokiClusterRoleNameFmt, instance)

	return []clusterRBACResource{
		{roleName: alloyName, bindingName: alloyName},
		{roleName: grafanaRole, bindingName: grafanaRole + rbacBindingSuffix},
		{roleName: lokiRole, bindingName: lokiRole + rbacBindingSuffix},
	}
}

func sanitizeK8sLabelValue(value string) (string, error) {
	value = strings.TrimSpace(value)
	if value == "" {
		return "", fmt.Errorf("instance display name is empty")
	}

	var builder strings.Builder
	builder.Grow(len(value))

	lastWasSeparator := false
	for _, char := range value {
		isLetter := (char >= 'a' && char <= 'z') || (char >= 'A' && char <= 'Z')
		isDigit := char >= '0' && char <= '9'
		isSeparator := char == '-' || char == '_' || char == '.'

		if isLetter || isDigit {
			builder.WriteRune(char)
			lastWasSeparator = false
			continue
		}

		if isSeparator {
			if !lastWasSeparator {
				builder.WriteRune(char)
				lastWasSeparator = true
			}
			continue
		}

		if !lastWasSeparator {
			builder.WriteByte('-')
			lastWasSeparator = true
		}
	}

	clean := strings.Trim(builder.String(), "-_.")
	if clean == "" {
		return "", fmt.Errorf("instance display name %q does not contain any valid characters", value)
	}

	if len(clean) > 63 {
		clean = strings.TrimRight(clean[:63], "-_.")
		if clean == "" {
			return "", fmt.Errorf("instance display name %q produced an empty label after truncation", value)
		}
	}

	return clean, nil
}

func (c *Client) Install(dto *models.InstanceDTO) error {
	if err := c.adoptDefaultServiceAccount(dto.Instance); err != nil {
		return err
	}

	return c.helm.Install(dto)
}

// adoptDefaultServiceAccount adds Helm ownership metadata and imagePullSecrets to the default
// ServiceAccount that Kubernetes auto-creates in every namespace, so Helm can manage it during
// install and pods can pull images from private registries.
func (c *Client) adoptDefaultServiceAccount(namespace string) error {
	clientSet, err := c.getClientSet()
	if err != nil {
		return err
	}

	pullSecret := config.Cloud.PullSecret
	patch := fmt.Sprintf(
		`{"metadata":{"labels":{"app.kubernetes.io/managed-by":"Helm"},"annotations":{"meta.helm.sh/release-name":%q,"meta.helm.sh/release-namespace":%q}},"imagePullSecrets":[{"name":%q}]}`,
		orchestyRepo,
		namespace,
		pullSecret,
	)

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	_, err = clientSet.CoreV1().ServiceAccounts(namespace).Patch(
		ctx,
		"default",
		types.MergePatchType,
		[]byte(patch),
		metav1.PatchOptions{},
	)

	return err
}

func (c *Client) Health() error {
	clientSet, err := c.getClientSet()
	if err != nil {
		return err
	}

	ctx, cancel := context.WithTimeout(context.Background(), requestTimeout)
	defer cancel()

	_, err = clientSet.CoreV1().Namespaces().List(ctx, metav1.ListOptions{Limit: 1})
	return err
}

func getSecretValue(secret *corev1.Secret, key string) string {
	if secret == nil || secret.Data == nil {
		return ""
	}

	return strings.TrimSpace(string(secret.Data[key]))
}

func extractMongoPassword(mongoDSN string) (string, error) {
	parsed, err := url.Parse(strings.TrimSpace(mongoDSN))
	if err != nil {
		return "", fmt.Errorf("parse mongodb dsn: %w", err)
	}

	if parsed.User == nil {
		return "", fmt.Errorf("mongodb dsn missing user info")
	}

	password, ok := parsed.User.Password()
	if !ok || strings.TrimSpace(password) == "" {
		return "", fmt.Errorf("mongodb dsn missing password")
	}

	return password, nil
}

func extractRabbitPassword(rabbitDSN string) (string, error) {
	parsed, err := url.Parse(strings.TrimSpace(rabbitDSN))
	if err != nil {
		return "", fmt.Errorf("parse rabbitmq dsn: %w", err)
	}

	if parsed.User == nil {
		return "", fmt.Errorf("rabbitmq dsn missing user info")
	}

	password, ok := parsed.User.Password()
	if !ok || strings.TrimSpace(password) == "" {
		return "", fmt.Errorf("rabbitmq dsn missing password")
	}

	return password, nil
}

func (c *Client) getClientSet() (kubernetes.Interface, error) {
	if c.clientSet != nil {
		return c.clientSet, nil
	}

	c.initMu.Lock()
	defer c.initMu.Unlock()

	if c.clientSet != nil {
		return c.clientSet, nil
	}

	var (
		cfg *rest.Config
		err error
	)

	if config.K8s.ClusterConfig != "" {
		cfg, err = clientcmd.BuildConfigFromFlags("", config.K8s.ClusterConfig)
		if err != nil {
			return nil, fmt.Errorf("error building kubernetes config from flags: %w", err)
		}
	} else {
		cfg, err = rest.InClusterConfig()
		if err != nil {
			return nil, fmt.Errorf("error getting config from cluster: %w", err)
		}
	}

	clientSet, err := kubernetes.NewForConfig(cfg)
	if err != nil {
		return nil, fmt.Errorf("failed to create clientSet: %w", err)
	}

	c.clientSet = clientSet
	return c.clientSet, nil
}
