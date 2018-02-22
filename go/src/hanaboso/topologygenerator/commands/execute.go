package commands

import (
	"bytes"
	"hanaboso/topologygenerator/log"
	"os/exec"
)

func Execute(command string, args ...string) (string, error, string) {

	log.Infof("CMD: %s %s", command, args)

	cmd := exec.Command(command, args...)
	var (
		out    bytes.Buffer
		stdErr bytes.Buffer
	)

	cmd.Stdout = &out
	cmd.Stderr = &stdErr

	err := cmd.Run()

	if err != nil {
		log.Infof("Exit:%s", err)
	}

	if out.Len() > 0 {
		log.Infof("OUT %s %s > %s\n", cmd.Path, cmd.Args, out.String())
	}

	if stdErr.Len() > 0 {
		log.Infof("STDERR %s\n", stdErr.String())
	}

	return out.String(), err, stdErr.String()
}
