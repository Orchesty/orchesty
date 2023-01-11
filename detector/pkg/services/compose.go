// Package services comment
package services

import (
	"bytes"
	"detector/pkg/config"
	"fmt"
	"os/exec"
	"strings"
)

type ContainerSystem interface {
	Check() ([]Container, error)
}

type Compose struct {
}

func (c Compose) Check() ([]Container, error) {
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
			downs = append(downs, Container{
				Name:    parts[0],
				Message: parts[1],
				Up:      strings.HasPrefix(parts[1], "Up"),
			})
		}
	}

	return downs, nil
}

func NewComposeSvc() Compose {
	return Compose{}
}
