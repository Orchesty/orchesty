package services

import (
	"context"
	"detector/pkg/config"
	"detector/pkg/utils/stringx"
	"io/ioutil"
	v1 "k8s.io/apimachinery/pkg/apis/meta/v1"
	"k8s.io/client-go/kubernetes"
	"k8s.io/client-go/rest"
	"time"
)

type KubernetesSvc struct {
	client *kubernetes.Clientset
	ns     string
}

func (k KubernetesSvc) getReplicaSets() (map[string]*Container, error) {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	res, err := k.client.
		AppsV1().
		ReplicaSets(k.ns).
		List(ctx, v1.ListOptions{
			LabelSelector: "app.kubernetes.io/instance=pipes",
		})

	cancel()
	if err != nil {
		return nil, err
	}

	containers := make(map[string]*Container)
	for _, it := range res.Items {
		if it.Status.Replicas == 0 {
			continue
		}

		name := stringx.RemovePostfix(it.Name, "-")
		containers[name] = &Container{
			Name:    name,
			Message: "",
			Up:      true,
			Desired: int(it.Status.Replicas),
			Ready:   int(it.Status.ReadyReplicas),
			Pods:    make([]ContainerPod, 0),
		}
	}

	return containers, err
}

func (k KubernetesSvc) getPods(containers map[string]*Container) ([]Container, error) {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	res, err := k.client.
		CoreV1().
		Pods(k.ns).
		List(ctx, v1.ListOptions{
			LabelSelector: "app.kubernetes.io/instance=pipes",
		})

	cancel()
	if err != nil {
		return nil, err
	}

	for _, it := range res.Items {
		name := stringx.RemovePostfix(it.Name, "-")
		name = stringx.RemovePostfix(name, "-")

		_, ok := containers[name]
		if !ok {
			continue
		}

		msg := ""
		sts := it.Status.ContainerStatuses[0]
		if sts.LastTerminationState.Terminated != nil {
			msg = sts.LastTerminationState.Terminated.Reason
		}

		containers[name].Pods = append(containers[name].Pods, ContainerPod{
			Up:       it.Status.Phase == "Running",
			Message:  msg,
			Restarts: int(sts.RestartCount),
			Created:  it.Status.StartTime.Time,
		})
	}

	var list []Container
	for _, it := range containers {
		list = append(list, *it)
	}

	return list, err
}

func (k KubernetesSvc) Check() ([]Container, error) {
	containers, err := k.getReplicaSets()
	if err != nil {
		return nil, err
	}

	return k.getPods(containers)
}

func NewKubernetesSvc() KubernetesSvc {
	c, err := rest.InClusterConfig()
	if err != nil {
		config.Logger.Fatal(err)
	}
	clientset, err := kubernetes.NewForConfig(c)
	if err != nil {
		config.Logger.Fatal(err)
	}

	tokenFile := "/var/run/secrets/kubernetes.io/serviceaccount/namespace"
	namespaceBytes, err := ioutil.ReadFile(tokenFile)
	if err != nil {
		config.Logger.Fatal(err)
	}
	namespace := string(namespaceBytes)

	return KubernetesSvc{
		client: clientset,
		ns:     namespace,
	}
}
