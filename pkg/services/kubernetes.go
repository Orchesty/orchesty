package services

import (
	"bytes"
	"detector/pkg/config"
	"os/exec"
	"strings"
)

func KubeContainerCheck() ([]Container, error) {
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
				Name:    parts[0],
				Message: parts[1],
				Up:      true, // TODO
			})
		}
	}

	return downs, nil
}
