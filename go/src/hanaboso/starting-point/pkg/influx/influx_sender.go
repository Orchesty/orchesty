package influx

import (
	"errors"
	"fmt"
	"go/types"
	"starting-point/pkg/config"
	"starting-point/pkg/udp"
	"strings"
	"time"

	log "github.com/sirupsen/logrus"
)

// SendMetrics sending metrics via UDP
func SendMetrics(tags map[string]interface{}, fields map[string]interface{}) {
	m, err := createMessage(tags, fields)
	if err != nil {
		log.Error(fmt.Sprintf("Creating data for Metrics failed. Error: %s", err))
	}

	udp.UDPSender.Send([]byte(m))
}

func createMessage(tags map[string]interface{}, fields map[string]interface{}) (m string, err error) {
	if len(fields) == 0 {
		return "", errors.New("fields must not be empty")
	}

	t := join(prepareTags(tags))
	f := join(prepareFields(fields))

	return fmt.Sprintf("%s,%s %s %s", config.Config.InfluxDB.Measurement, t, f, string(time.Now().UnixNano())), nil
}

func join(items map[string]interface{}) (res string) {
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

func prepareTags(tags map[string]interface{}) map[string]interface{} {
	for k, item := range tags {
		if item == "" {
			tags[k] = "\"\""
		} else if item == types.IsBoolean {
			switch item {
			case true:
				tags[k] = "true"
				break
			case false:
				tags[k] = "false"
				break
			}
		} else if item == nil {
			tags[k] = "null"
		}
	}

	return tags
}

func prepareFields(fields map[string]interface{}) map[string]interface{} {
	for k, item := range fields {
		if item == types.IsInteger {
			fields[k] = fmt.Sprintf("%d", item)
		} else if item == types.IsBoolean {
			switch item {
			case true:
				fields[k] = "true"
				break
			case false:
				fields[k] = "false"
				break
			}
		} else if item == nil {
			fields[k] = "null"
		} else if item == types.IsString {
			fields[k] = escapeString(fmt.Sprintf("%s", item))
		}
	}

	return fields
}

func escapeString(s string) string {
	s = strings.Replace(s, "\"", "\\\"", -1)

	return fmt.Sprintf("\"%s\"", s)
}
