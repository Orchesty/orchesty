package memory

import (
	"github.com/hanaboso/pipes/bridge/pkg/model"
)

type Publisher struct {
	delivery chan<- *model.ProcessMessage
}

func (p Publisher) Publish(dto *model.ProcessMessage) error {
	// TODO tohle se musí nějak upravit
	// nezapomenout published timestamp
	p.delivery <- dto

	return nil
}
