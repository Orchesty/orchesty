package services

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"os/exec"
	"strings"

	"detector/pkg/config"
)

type RabbitMqStats struct{}

type Queue struct {
	Messages  int    `json:"messages"`
	Consumers int    `json:"consumers"`
	Name      string `json:"name"`
}

type Container struct {
	Name   string `json:"name"`
	Status string `json:"status"`
}

func (svc RabbitMqStats) KubeContainerCheck() ([]Container, error) {
	cmdRunning := []string{"get", "pods", "--namespace", config.Generator.Network}

	// TODO
	var out bytes.Buffer
	cmd := exec.Command("kubectl", cmdRunning...)
	cmd.Stdout = &out
	_ = cmd.Run()
	status := out.String()

	downs := make([]Container, 0)

	for _, line := range strings.Split(status, "\n") {
		if len(line) > 0 {
			parts := strings.SplitN(line, " ", 2)
			downs = append(downs, Container{
				Name:   parts[0],
				Status: parts[1],
			})
		}
	}

	return downs, nil
}

func (svc RabbitMqStats) DockerContainerCheck() ([]Container, error) {
	//cmdAll := []string{"network", "inspect" ,"-f", "{{ range $key, $value := .Containers }}{{printf \"%s\\n\" .Name}}{{ end }}", network}
	cmdRunning := []string{"ps", "-a", "-f", fmt.Sprintf("network=%s", config.Generator.Network), "--format", "{{.Names}} {{.Status}}"}

	var out bytes.Buffer
	cmd := exec.Command("docker", cmdRunning...)
	cmd.Stdout = &out
	_ = cmd.Run()
	status := out.String()

	downs := make([]Container, 0)

	for _, line := range strings.Split(status, "\n") {
		if len(line) > 0 {
			parts := strings.SplitN(line, " ", 2)
			if !strings.HasPrefix(parts[1], "Up") {
				downs = append(downs, Container{
					Name:   parts[0],
					Status: parts[1],
				})
			}
		}
	}

	return downs, nil
}

// GatherQueuesInfo returns RabbitMq queues stats
func (svc RabbitMqStats) GatherQueuesInfo() ([]Queue, error) {
	var list []Queue

	url := fmt.Sprintf("%s/api/queues", strings.TrimRight(config.RabbitMQ.Host, "/"))
	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		return nil, err
	}

	req.SetBasicAuth(config.RabbitMQ.Username, config.RabbitMQ.Password)

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}

	defer func() { _ = res.Body.Close() }()

	if err = json.NewDecoder(res.Body).Decode(&list); err != nil {
		return nil, err
	}

	return list, nil
}

// NewRabbitMqFetchSvc creates RabbitMq fetcher
func NewRabbitMqFetchSvc() RabbitMqStats {
	return RabbitMqStats{}
}
