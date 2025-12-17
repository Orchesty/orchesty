package server

import (
	"github.com/gin-gonic/gin"
)

func apiVersion(major string, minor string) gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Header("X-Major-Version", major)
		c.Header("X-Minor-Version", minor)
		c.Next()
	}
}
