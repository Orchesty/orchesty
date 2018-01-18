package rabbitmq

import (
	"hanaboso.com/topologygenerator/model"
	"hanaboso.com/topologygenerator/rabbitmq/consumers"
	"github.com/spf13/viper"
)

func getCallback(name string) model.CallbackFunction {

	var callbacks = make(map[string]model.CallbackFunction)

	callbacks["topologyHandle"] = &consumers.TopologyConsumer{
		Db: model.CreateConnection(viper.GetString("mongodb.host"), viper.GetInt("mongodb.port")),
	}

	function, ok := callbacks[name]

	if ok {
		return function
	} else {
		panic("Callback not found.")
	}
}
