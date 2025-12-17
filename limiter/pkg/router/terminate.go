package router

import (
	"net/http"

	"github.com/julienschmidt/httprouter"
	"go.mongodb.org/mongo-driver/v2/bson"
)

func Terminate(field string) route {
	return func(writer http.ResponseWriter, _ *http.Request, params httprouter.Params, container Container) {
		key := params.ByName("key")
		err := container.Mongo.Clear(bson.D{{field, key}})
		if err != nil {
			errorResponse(writer, err)
			return
		}

		response(writer, struct{}{})
	}
}
