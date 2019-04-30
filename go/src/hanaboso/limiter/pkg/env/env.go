package env

import "os"

// GetEnv returns the ENV variable value or returns the default value if not set
func GetEnv(key, defaultValue string) string {
	value := os.Getenv(key)
	if len(value) == 0 {
		return defaultValue
	}
	return value
}
