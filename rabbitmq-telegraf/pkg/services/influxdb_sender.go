package services

import (
	"bytes"
	"fmt"
	"net/http"
	"rabbitmq-telegraf/pkg/config"
	"time"
)

type InfluxDbSender struct {
	Host        string
	Database    string
	Retention   string
	Measurement string
}

func (db *InfluxDbSender) Send(metrics []Queue) error {
	url := db.getUrl()

	for _, m := range metrics {
		data := db.prepData(m)

		res, err := http.Post(url, "text/html", bytes.NewBufferString(data))
		if err != nil {
			return err
		}
		_ = res.Body.Close()

		if res.StatusCode >= 300 {
			return fmt.Errorf("InfluxDb sender - response status code: %d", res.StatusCode)
		}
	}

	return nil
}

func (db *InfluxDbSender) getUrl() string {
	return fmt.Sprintf("%s/write?db=%s&rp=%s", db.Host, db.Database, db.Retention)
}

func (db *InfluxDbSender) prepData(m Queue) string {
	return fmt.Sprintf("%s,queue=%s messages=%di %d", db.Measurement, m.Name, m.Messages, time.Now().UnixNano())
}

func NewInfluxDbSenderSvc() SenderSvc {
	return &InfluxDbSender{
		Host:        config.InfluxDb.DSN,
		Database:    config.InfluxDb.Database,
		Retention:   config.InfluxDb.Retention,
		Measurement: config.InfluxDb.Measurement,
	}
}
