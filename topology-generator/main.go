package main

import (
	"topology-generator/cmd"
	_ "topology-generator/pkg/config"
)

func main() {
	cmd.Execute()
}
