package utils

import (
	"fmt"
	"starting-point/pkg/storage"
	"strings"
)

// GenerateTplgName generates queue name from topology & node id's and name's
func GenerateTplgName(t storage.Topology) string {
	nodePart := fmt.Sprintf("%s-%s", t.Node.ID.Hex(), webalize(getName(t.Node.Name)))
	if len(nodePart) > 63 {
		nodePart = nodePart[0:63]
	}

	return fmt.Sprintf("pipes.%s.%s", t.ID.Hex(), nodePart)
}

func webalize(name string) string {
	return strings.TrimSpace(strings.ToLower(name))
}

func getName(name string) string {
	result := ""

	for _, item := range strings.Split(name, "-") {
		result += item[0:3] + "-"
	}

	return strings.TrimSuffix(result, "-")
}
