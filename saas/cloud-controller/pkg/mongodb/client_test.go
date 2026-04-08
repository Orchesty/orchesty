package mongodb

import (
	"errors"
	"fmt"
	"testing"
	"time"

	"cloud-controller/pkg/models"
)

func TestNewClient(t *testing.T) {
	client := NewClient()
	if client == nil {
		t.Fatal("expected non-nil client")
	}
}

func TestCreateUserClientNotInitialized(t *testing.T) {
	client := NewClient()

	_, err := client.CreateUser(&models.InstanceDTO{})
	if !errors.Is(err, errClientNotInitialized) {
		t.Fatalf("expected errClientNotInitialized, got %v", err)
	}
}

func TestGetUserClientNotInitialized(t *testing.T) {
	client := NewClient()

	_, err := client.GetUser("test-user")
	if !errors.Is(err, errClientNotInitialized) {
		t.Fatalf("expected errClientNotInitialized, got %v", err)
	}
}

func TestDeleteUserClientNotInitialized(t *testing.T) {
	client := NewClient()

	_, err := client.DeleteUser("test-user")
	if !errors.Is(err, errClientNotInitialized) {
		t.Fatalf("expected errClientNotInitialized, got %v", err)
	}
}

func TestPingClientNotInitialized(t *testing.T) {
	client := NewClient()

	err := client.Ping()
	if !errors.Is(err, errClientNotInitialized) {
		t.Fatalf("expected errClientNotInitialized, got %v", err)
	}
}

func TestDisconnectWithoutConnection(t *testing.T) {
	client := NewClient()

	client.Disconnect()
}

func TestInitAndPingSuccess(t *testing.T) {
	client := NewClient()
	if err := client.Init(); err != nil {
		t.Fatalf("expected init without error, got %v", err)
	}
	t.Cleanup(client.Disconnect)

	if err := client.Ping(); err != nil {
		t.Fatalf("expected successful ping after init, got %v", err)
	}
}

func TestCreateGetDeleteUserWithInitializedClient(t *testing.T) {
	client := NewClient()
	if err := client.Init(); err != nil {
		t.Fatalf("expected init without error, got %v", err)
	}
	t.Cleanup(client.Disconnect)

	instance := fmt.Sprintf("test-instance-%d", time.Now().UnixNano())
	dto := &models.InstanceDTO{
		Instance:            instance,
		InstanceDisplayName: "MongoDB test instance",
		MongoPassword:       "mongo-test-password",
	}

	if _, err := client.CreateUser(dto); err != nil {
		t.Fatalf("expected create user without error, got %v", err)
	}

	t.Cleanup(func() {
		_, _ = client.DeleteUser(instance)
	})

	userInfo, err := client.GetUser(instance)
	if err != nil {
		t.Fatalf("expected get user without error, got %v", err)
	}
	if len(userInfo.Users) == 0 {
		t.Fatal("expected at least one user in usersInfo response")
	}
	if userInfo.Users[0].User != instance {
		t.Fatalf("expected user %q, got %q", instance, userInfo.Users[0].User)
	}

	if _, err := client.DeleteUser(instance); err != nil {
		t.Fatalf("expected delete user without error, got %v", err)
	}
}
