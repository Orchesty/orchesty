package rabbitmq

import (
	"github.com/spf13/viper"
)

func Consumers() {
	conn := CreateConnection(
		viper.GetString("rabbitmq.host"),
		viper.GetInt("rabbitmq.port"),
		viper.GetString("rabbitmq.user"),
		viper.GetString("rabbitmq.pass"),
	)

	for _, v := range viper.GetStringMap("queues") {
		q := v.(map[string]interface{})

		var (
			name       string
			durable    = false
			autoDelete = false
			exclusive  = false
			nowait     = false
		)

		if q["name"] != nil {
			name = q["name"].(string)
		}

		if q["durable"] != nil {
			durable = q["durable"].(bool)
		}

		if q["auto_delete"] != nil {
			autoDelete = q["auto_delete"].(bool)
		}

		if q["exclusive"] != nil {
			exclusive = q["exclusive"].(bool)
		}

		if q["nowait"] != nil {
			nowait = q["nowait"].(bool)
		}

		go conn.Consumer(
			Queue{
				Name:       name,
				Durable:    durable,
				AutoDelete: autoDelete,
				Exclusive:  exclusive,
				NoWait:     nowait,
				Callback:   getCallback(q["callback"].(string)),
			},
			Qos{
				PrefetchCount: 1,
				PrefetchSize:  0,
				Global:        false,
			},
		)
	}
}
