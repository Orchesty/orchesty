package commands

import (
	"hanaboso/topologygenerator/model"
	"io/ioutil"
	"os"
)

func WriteFile(dstDir string, file string, content []byte) error {
	err := CreateDirectory(dstDir)
	if err != nil {
		panic(model.AppError{Message: err.Error(), Type: model.APP})
	}
	err = ioutil.WriteFile(dstDir+"/"+file, content, os.FileMode(0777))

	return err
}

func CreateDirectory(directory string) error {
	err := os.MkdirAll(directory, os.FileMode(0777))

	return err
}
