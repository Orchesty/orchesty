package utils

import "fmt"

type Error struct {
	Code    int
	Message string
}

func (error Error) Error() string {
	return fmt.Sprintf("Error %d: %s", error.Code, error.Message)
}
