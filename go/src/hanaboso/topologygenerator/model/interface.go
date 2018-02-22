package model

import (
	"net/http"

	"github.com/streadway/amqp"
)

type URLHandler interface {
	GenerateAction(w http.ResponseWriter, r *http.Request)
	RunAction(w http.ResponseWriter, r *http.Request)
	StopAction(w http.ResponseWriter, r *http.Request)
	DeleteAction(w http.ResponseWriter, r *http.Request)
	InfoAction(w http.ResponseWriter, r *http.Request)
	Close()
}

type CallbackFunction interface {
	Handle(msgs <-chan amqp.Delivery)
}
