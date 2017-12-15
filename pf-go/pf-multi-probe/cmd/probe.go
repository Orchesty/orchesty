package main

import (
	"pf-go/pf-multi-probe/pkg/probe"
)

func main() {
	srv := probe.Server{Topologies: make(probe.TopologiesMap)}
	srv.Start()
}
