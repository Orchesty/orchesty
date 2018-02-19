package compose

import (
	"fmt"
	"net/http"
	"os"

	"hanaboso/utils/topology"
)

func Delete(topology *topology.Topology) (int, string) {

	var (
		status  int
		message string
	)

	if topology.ID.Hex() == "" {
		return http.StatusInternalServerError, "Topology missing"
	}

	dstDir := GetDstDir(topology)

	err := os.RemoveAll(dstDir)

	//TODO add queue handler

	if err != nil {
		status = http.StatusInternalServerError
		message = err.Error()
	} else {
		status = http.StatusOK
		message = fmt.Sprintf("ID: %s", topology.ID.Hex())
	}

	return status, message
}
