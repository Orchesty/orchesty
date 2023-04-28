package router

import (
	"github.com/julienschmidt/httprouter"
	"net/http"
)

func Destroy(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params, container Container) {
	response(writer, "{}")
	container.RabbitMq.Destroy()

	<-container.CloseApp
	container.AppCancel()
	container.RabbitMq.Close()
	container.CloseApp <- struct{}{}
}
