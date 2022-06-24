package services

import (
	"bytes"
	"os/exec"
	"strconv"
	"strings"
)

func KubeContainerCheck() ([]Container, error) {
	cmdPods := []string{"get", "pods", "-l", "app.kubernetes.io/instance=pipes"}
	// kubectl get pods -l app.kubernetes.io/instance=pipes
	// NAME                                                  READY   STATUS    RESTARTS   AGE
	// pipes-backend-678468cccf-qct9w                        1/1     Running   0          46d

	cmdReplicas := []string{"get", "rs", "-l", "app.kubernetes.io/instance=pipes", "|", "awk", "'$2!=0'"}
	// kubectl get rs -l app.kubernetes.io/instance=pipes | awk '$2!=0'
	// NAME                                            DESIRED   CURRENT   READY   AGE
	// pipes-backend-678468cccf                        1         1         1       97d

	var out bytes.Buffer
	cmd := exec.Command("kubectl", cmdReplicas...)
	cmd.Stdout = &out
	_ = cmd.Run()
	replicasInfo := out.String()

	cmd = exec.Command("kubectl", cmdPods...)
	out.Reset()
	cmd.Stdout = &out
	_ = cmd.Run()
	podsInfo := out.String()

	containers := make(map[string]Container, 10)

	for i, line := range strings.Split(replicasInfo, "\n") {
		if i > 0 && len(line) > 0 {
			rs := strings.Fields(line)
			desired, _ := strconv.Atoi(rs[1])
			ready, _ := strconv.Atoi(rs[3])

			containers[""] = Container{
				Name: rs[0][:strings.LastIndex(rs[0], "-")],
				//Message: "",
				Up:      true,
				Desired: desired,
				Ready:   ready,
				Pods:    nil,
			}
		}
	}

	for i, line := range strings.Split(podsInfo, "\n") {
		if i > 0 && len(line) > 0 {
			pod := strings.Fields(line)
			name := pod[0][:strings.LastIndex(pod[0], "-")]
			name = name[:strings.LastIndex(name, "-")]
			container, ok := containers[name]
			if !ok {
				continue
			}

			conts := strings.Split(pod[1], "/")
			restarts, _ := strconv.Atoi(pod[3])

			container.Pods = append(container.Pods, ContainerPod{
				Up:       conts[0] == conts[1],
				Message:  pod[2],
				Restarts: restarts,
				Age:      pod[4],
			})
		}
	}

	var res []Container
	for _, container := range containers {
		for _, pod := range container.Pods {
			if !pod.Up {
				container.Up = false
				if len(container.Message) > 0 {
					container.Message += ","
				}
				container.Message += container.Message
			}
		}

		res = append(res, container)
	}

	return res, nil
}
