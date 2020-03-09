package utils

import "fmt"

// Error represents error
type Error struct {
	Code    int
	Message string
}

// Error returns string representation of error
func (error Error) Error() string {
	return fmt.Sprintf("Error %d: %s", error.Code, error.Message)
}
