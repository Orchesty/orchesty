package models

import (
	"strings"
	"testing"
	"unicode"
)

func TestNewInstanceDTO(t *testing.T) {
	customizations := Customizations{
		Workers: []Worker{
			{
				Name:    "default",
				Image:   "hanaboso/demo-worker:latest",
				SdkType: "nodejs",
			},
		},
		UserName: "user@test.local",
	}

	dto, err := NewInstanceDTO("Test Instance", "prefix", "", customizations)
	if err != nil {
		t.Fatalf("expected NewInstanceDTO without error, got %v", err)
	}

	if !strings.HasPrefix(dto.Instance, "instance-") {
		t.Fatalf("expected instance prefix 'instance-', got %q", dto.Instance)
	}
	if len(dto.Instance) != len("instance-")+10 {
		t.Fatalf("expected instance length %d, got %d", len("instance-")+10, len(dto.Instance))
	}
	if dto.InstanceDisplayName != "Test Instance" {
		t.Fatalf("unexpected InstanceDisplayName %q", dto.InstanceDisplayName)
	}
	if dto.UserName != "user@test.local" {
		t.Fatalf("unexpected UserName %q", dto.UserName)
	}
	if len(dto.Customizations.Workers) != len(customizations.Workers) {
		t.Fatalf("unexpected number of workers %d", len(dto.Customizations.Workers))
	}
	if dto.Customizations.Workers[0].Name != "default" || dto.Customizations.Workers[0].Image != "hanaboso/demo-worker:latest" {
		t.Fatalf("unexpected customizations %+v", dto.Customizations)
	}

	assertLength(t, dto.MongoPassword, 16, "MongoPassword")
	assertLength(t, dto.RabbitPassword, 16, "RabbitPassword")
	assertLength(t, dto.UserPassword, 16, "UserPassword")
	assertLength(t, dto.BackendJwtKey, 64, "BackendJwtKey")
	assertLength(t, dto.CryptSecret, 64, "CryptSecret")
	assertLength(t, dto.OrchestyApiKey, 64, "OrchestyApiKey")

	for _, char := range dto.Instance[len("instance-"):] {
		if unicode.IsUpper(char) {
			t.Fatalf("expected generated instance suffix to contain no uppercase letters, got %q", dto.Instance)
		}
	}
}

func TestToInstanceInfo(t *testing.T) {
	dto := &InstanceDTO{
		Instance:            "instance-test",
		InstanceId:          "test",
		InstanceDisplayName: "Test Instance",
		UserName:            "user@test.local",
		UserPassword:        "secret",
		MongoPassword:       "mongo-secret",
		RabbitPassword:      "rabbit-secret",
	}

	info := dto.ToInstanceInfo(true)

	if info.Instance != dto.InstanceId {
		t.Fatalf("unexpected Instance %q", info.Instance)
	}
	if info.InstanceDisplayName != dto.InstanceDisplayName {
		t.Fatalf("unexpected InstanceDisplayName %q", info.InstanceDisplayName)
	}
	if info.UserName != dto.UserName {
		t.Fatalf("unexpected UserName %q", info.UserName)
	}
	if info.UserPassword != dto.UserPassword {
		t.Fatalf("unexpected UserPassword %q", info.UserPassword)
	}
}

func TestToInstanceInfoWithoutCredentials(t *testing.T) {
	dto := &InstanceDTO{
		Instance:            "instance-test",
		InstanceId:          "test",
		InstanceDisplayName: "Test Instance",
		UserName:            "user@test.local",
		UserPassword:        "secret",
		MongoPassword:       "mongo-secret",
		RabbitPassword:      "rabbit-secret",
	}

	info := dto.ToInstanceInfo(false)

	if info.Instance != dto.InstanceId {
		t.Fatalf("unexpected Instance %q", info.Instance)
	}
	if info.InstanceDisplayName != dto.InstanceDisplayName {
		t.Fatalf("unexpected InstanceDisplayName %q", info.InstanceDisplayName)
	}
	if info.UserName != "" {
		t.Fatalf("expected UserName to be empty, got %q", info.UserName)
	}
	if info.UserPassword != "" {
		t.Fatalf("expected UserPassword to be empty, got %q", info.UserPassword)
	}
}

func TestNewInstanceDTOFromExistingData(t *testing.T) {
	customizations := Customizations{
		Workers: []Worker{
			{
				Name:    "default",
				Image:   "hanaboso/demo-worker:latest",
				SdkType: "nodejs",
			},
		},
	}
	dto, err := NewInstanceDTOFromExistingData(ExistingInstanceData{
		Instance:            " instance-test ",
		InstanceDisplayName: " Test Instance ",
		UserName:            " admin@example.com ",
		UserPassword:        " user-secret ",
		MongoPassword:       " mongo-secret ",
		RabbitPassword:      " rabbit-secret ",
		BackendJwtKey:       " backend-key ",
		CryptSecret:         " crypt-secret ",
		OrchestyApiKey:      " api-key ",
		Customizations:      customizations,
	})
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if dto.Instance != "instance-test" {
		t.Fatalf("unexpected instance %q", dto.Instance)
	}
	if dto.InstanceDisplayName != "Test Instance" {
		t.Fatalf("unexpected display name %q", dto.InstanceDisplayName)
	}
	if dto.UserName != "admin@example.com" {
		t.Fatalf("unexpected userName %q", dto.UserName)
	}
	if dto.UserPassword != "user-secret" {
		t.Fatalf("unexpected userPassword %q", dto.UserPassword)
	}
	if len(dto.Customizations.Workers) != len(customizations.Workers) {
		t.Fatalf("unexpected number of workers %d", len(dto.Customizations.Workers))
	}
	if dto.Customizations.Workers[0].Name != "default" || dto.Customizations.Workers[0].Image != "hanaboso/demo-worker:latest" {
		t.Fatalf("unexpected customizations %+v", dto.Customizations)
	}
}

func TestNewInstanceDTOFromExistingDataRequiresFields(t *testing.T) {
	_, err := NewInstanceDTOFromExistingData(ExistingInstanceData{})
	if err == nil || err.Error() != "instance is required" {
		t.Fatalf("expected missing instance error, got %v", err)
	}

	_, err = NewInstanceDTOFromExistingData(ExistingInstanceData{Instance: "instance-test"})
	if err == nil || err.Error() != "instanceDisplayName is required" {
		t.Fatalf("expected missing instanceDisplayName error, got %v", err)
	}
}

func TestNewInstanceDTOWithForceInstanceId(t *testing.T) {
	dto, err := NewInstanceDTO("Test Instance", "prefix", "myinstanceid", Customizations{})
	if err != nil {
		t.Fatalf("expected NewInstanceDTO without error, got %v", err)
	}

	if dto.Instance != "instance-myinstanceid" {
		t.Fatalf("expected instance 'instance-myinstanceid', got %q", dto.Instance)
	}
	if dto.InstanceId != "myinstanceid" {
		t.Fatalf("expected instanceId 'myinstanceid', got %q", dto.InstanceId)
	}
}

func assertLength(t *testing.T, value string, expected int, field string) {
	t.Helper()

	if len(value) != expected {
		t.Fatalf("expected %s length %d, got %d", field, expected, len(value))
	}
	if value == "" {
		t.Fatalf("expected %s to be non-empty", field)
	}
}
