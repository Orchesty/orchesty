package commands

import (
	"hanaboso/topologygenerator/model"
	"io/ioutil"
	"os"
)

// WriteFile creates new file in given directory
func WriteFile(directory string, filename string, content []byte) error {
	err := CreateDirectory(directory)
	if err != nil {
		panic(model.AppError{Message: err.Error(), Type: model.APP})
	}
	err = ioutil.WriteFile(directory+"/"+filename, content, os.FileMode(0777))

	return err
}

// CreateDirectory creates new empty directory or does nothing if it already exists
func CreateDirectory(directory string) error {
	return os.MkdirAll(directory, os.FileMode(0777))
}

// RemoveDirectory deletes the desired directory with all of it's contents
func RemoveDirectory(directory string) error {
	return os.RemoveAll(directory)
}
