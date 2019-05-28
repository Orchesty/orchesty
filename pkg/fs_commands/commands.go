package fs_commands

import (
	"bytes"
	"io/ioutil"
	"os"
	"os/exec"

	log "github.com/sirupsen/logrus"
)

// WriteFile creates new file in given directory
func WriteFile(directory string, filename string, content []byte) error {
	err := createDirectory(directory)

	if err != nil {
		return err
	}
	err = ioutil.WriteFile(directory+"/"+filename, content, os.FileMode(0777))

	return err
}

// createDirectory creates new empty directory or does nothing if it already exists
func createDirectory(directory string) error {
	return os.MkdirAll(directory, os.FileMode(0777))
}

// RemoveDirectory deletes the desired directory with all of it's contents
func RemoveDirectory(directory string) error {
	return os.RemoveAll(directory)
}

func Execute(command string, args ...string) (error, bytes.Buffer, bytes.Buffer) {

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

	return err, out, stdErr
}
