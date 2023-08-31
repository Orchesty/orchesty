package bridge

import (
	"context"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/rs/zerolog/log"
	"testing"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/rabbitmq"
	"github.com/hanaboso/pipes/bridge/pkg/topology"
)

// Checks if app stops upon context cancel -> no actual asserts of what is closed
func TestBridge_Shutdown(t *testing.T) {
	go testTimeout(5)
	mongodb := mongo.NewMongoDb()
	rabbit := rabbitmq.NewRabbitMQ()
	topoSvc := topology.NewTopologySvc(rabbit)
	topo, err := topoSvc.Parse(config.App.TopologyJSON)
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	br := NewBridge(rabbit, mongodb, topo)

	ctx, cancel := context.WithCancel(context.Background())
	go func() {
		time.Sleep(time.Second)
		cancel()
	}()

	br.Run(ctx)
}

// "global" fce... should Go actually allow something like it - or create shared tests package
func testTimeout(seconds int) {
	ctx, _ := context.WithTimeout(context.Background(), time.Duration(seconds)*time.Second)
	<-ctx.Done()
	panic("timeout") // t.FailNow nefunguje pro zastavení
}
