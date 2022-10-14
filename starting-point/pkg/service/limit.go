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

func GetApplicationLimits(user string, topology storage.Topology) (string, error) {
	apps := make(map[string][]string)
	for _, app := range topology.Applications {
		if _, ok := apps[app.Host]; !ok {
			apps[app.Host] = make([]string, 0)
		}

		apps[app.Host] = append(apps[app.Host], app.Key)
	}

	limits := ""
	for host, keys := range apps {
		body := map[string]interface{}{
			"user":         user,
			"applications": keys,
		}
		jsonValue, _ := json.Marshal(body)

		resp, err := http.Post(
			fmt.Sprintf("%s/applications/limits", strings.TrimRight(host, "/")),
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

		limits = fmt.Sprintf("%s;%s", limits, strings.Join(resData, ";"))
	}

	return limits, nil
}
