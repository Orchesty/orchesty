package service

import (
	"fmt"
	"io/ioutil"
	"os/exec"
	"strings"
	"time"

	"cron/pkg/storage"
	"github.com/hanaboso/go-log/pkg/zap"

	log "github.com/hanaboso/go-log/pkg"
)

// Interface represents abstract cron implementation
type Interface interface {
	Start()
	Stop()
}

type cron struct {
	ticker *time.Ticker
	log    log.Logger
}

// Cron represents specific cron implementation
var Cron Interface = &cron{}

// Start starts cron
func (c *cron) Start() {
	if c.log == nil {
		c.log = zap.NewLogger()
	}

	c.write()

	if c.ticker == nil {
		c.ticker = time.NewTicker(time.Minute)
	}

	go func() {
		for range c.ticker.C {
			c.write()
		}
	}()
}

// Stop stops cron
func (c *cron) Stop() {
	c.ticker.Stop()
}

func (c *cron) write() {
	crons, err := storage.MongoDB.GetAll()

	if err != nil {
		return
	}

	var content []string

	for _, cron := range crons {
		content = append(content, fmt.Sprintf("%s %s", cron.Time, cron.Command))
	}

	c.logContext().Info("Updating %d CRONs...", len(crons))

	if err := ioutil.WriteFile("/etc/crontabs/root", []byte(strings.Join(content, "\n")), 0777); err != nil {
		c.logContext().Error(err)
	}

	if _, err := exec.Command("crontab", "/etc/crontabs/root").Output(); err != nil {
		c.logContext().Error(err)
	}
}

func (c *cron) logContext() log.Logger {
	return c.log.WithFields(map[string]interface{}{
		"service": "cron",
		"type":    "cron-service-loop",
	})
}
