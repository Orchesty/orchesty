package service

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"starting-point/pkg/storage"
	"strings"
)

type hostInfo struct {
	keys []string
	sdk  string
}

func GetApplicationLimits(user string, topology storage.Topology) (string, error) {
	hosts := make(map[string]*hostInfo)
	for _, app := range topology.Applications {
		if _, ok := hosts[app.Host]; !ok {
			hosts[app.Host] = &hostInfo{sdk: app.Sdk}
		}

		hosts[app.Host].keys = append(hosts[app.Host].keys, app.Key)
	}

	limits := ""
	for host, info := range hosts {
		body := map[string]interface{}{
			"user":         user,
			"applications": info.keys,
		}
		jsonValue, _ := json.Marshal(body)

		if !strings.HasPrefix(host, "http") {
			host = fmt.Sprintf("http://%s", host)
		}

		resp, err := http.Post(
			fmt.Sprintf("%s/applications/sdk/%s/limits", strings.TrimRight(host, "/"), info.sdk),
			"application/json",
			bytes.NewBuffer(jsonValue),
		)

		if err != nil {
			return "", err
		}

		responseStr, err := ioutil.ReadAll(resp.Body)
		_ = resp.Body.Close()
		if err != nil {
			return "", err
		}

		var resData []string
		if err = json.Unmarshal(responseStr, &resData); err != nil {
			return "", err
		}

		if len(resData) > 0 {
			delimiter := ""
			if len(limits) > 0 {
				delimiter = ";"
			}
			limits = fmt.Sprintf("%s%s%s", limits, delimiter, strings.Join(resData, ";"))
		}
	}

	return limits, nil
}
