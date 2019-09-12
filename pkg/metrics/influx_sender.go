package metrics

import (
	"errors"
	"fmt"
	"reflect"
	"starting-point/pkg/config"
	"starting-point/pkg/udp"
	"strings"
	"time"

	log "github.com/sirupsen/logrus"
)

var i int
var i8 int8
var i6 int16
var i2 int32
var i4 int64
var f4 float64
var f2 float32
var b bool
var s string

type influx struct{}

// SendMetrics sending metrics via UDP
func (inf influx) SendMetrics(tags map[string]interface{}, fields map[string]interface{}) {
	m, err := inf.createMessage(tags, fields)
	if err != nil {
		log.Error(fmt.Sprintf("Creating data for Metrics failed. Error: %s", err))
	}

	udp.UDP.Send([]byte(m))
}

func (inf influx) createMessage(tags map[string]interface{}, fields map[string]interface{}) (m string, err error) {
	if len(fields) == 0 {
		return "", errors.New("fields must not be empty")
	}

	t := inf.join(inf.prepareItems(tags, false))
	f := inf.join(inf.prepareItems(fields, true))

	return fmt.Sprintf("%s,%s %s %d", config.Config.InfluxDB.Measurement, t, f, time.Now().UnixNano()), nil
}

func (inf influx) join(items map[string]interface{}) (res string) {
	res = ""

	if len(items) == 0 {
		return
	}

	for k, item := range items {
		res += fmt.Sprintf("%s=%s,", k, item)
	}

	res = strings.TrimSuffix(res, ",")

	return
}

func (inf influx) prepareItems(items map[string]interface{}, escape bool) map[string]interface{} {
	for k, item := range items {
		t := reflect.TypeOf(item)

		if item == "" {
			items[k] = "\"\""
		} else if t == reflect.TypeOf(i) || t == reflect.TypeOf(i8) || t == reflect.TypeOf(i6) || t == reflect.TypeOf(i2) || t == reflect.TypeOf(i4) {
			items[k] = fmt.Sprintf("%d", item)
		} else if t == reflect.TypeOf(f2) || t == reflect.TypeOf(f4) {
			items[k] = fmt.Sprintf("%f", item)
		} else if t == reflect.TypeOf(b) {
			switch item {
			case true:
				items[k] = "true"
				break
			case false:
				items[k] = "false"
				break
			}
		} else if item == nil {
			items[k] = "null"
		} else if t == reflect.TypeOf(s) {
			if escape == true {
				items[k] = inf.escapeString(fmt.Sprintf("%s", item))
			} else {
				items[k] = fmt.Sprintf("%s", item)
			}
		} else {
			delete(items, k)
		}
	}

	return items
}

func (inf influx) escapeString(s string) string {
	s = strings.Replace(s, "\"", "\\\"", -1)

	return fmt.Sprintf("\"%s\"", s)
}

func newInfluxSender() Sender {
	return influx{}
}
