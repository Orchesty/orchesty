package router

import (
	"github.com/julienschmidt/httprouter"
	"net/http"
)

func Clear(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params, container Container) {
	<-container.CloseApp
	response(writer, "{}")

	container.AppCancel()
	container.RabbitMq.DeleteQueues()
	container.CloseApp <- struct{}{}
}
