package cmd

import (
	"context"
	"github.com/hanaboso/pipes/bridge/pkg/bridge"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/hanaboso/pipes/bridge/pkg/rabbitmq"
	"github.com/hanaboso/pipes/bridge/pkg/router"
	"github.com/hanaboso/pipes/bridge/pkg/topology"
	"github.com/hanaboso/pipes/bridge/pkg/worker"
	"github.com/rs/zerolog/log"
	"github.com/spf13/cobra"
	"net/http"
	"os"
	"os/signal"
	"syscall"
)

func init() {
	rootCmd.AddCommand(startCmd)
}

var startCmd = &cobra.Command{
	Use:   "start",
	Short: "Starts a bridge",
	Long: `
		APP_DEBUG - enable debug log
		TOPOLOGY_JSON - path to the topology config
		RABBITMQ_DSN - rabbitmq dsn
	`,
	Run: startBridge,
}

func startBridge(_ *cobra.Command, _ []string) {
	log.Info().Msg("Starting...")
	// Construct services
	mongodb := mongo.NewMongoDb()
	worker.InitializeWorkers(mongodb)

	rabbit := rabbitmq.NewRabbitMQ()
	topoSvc := topology.NewTopologySvc(rabbit)
	topo, err := topoSvc.Parse(config.App.TopologyJSON)
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	ctx, cancel := context.WithCancel(context.Background())
	marker := make(chan struct{}, 1)
	marker <- struct{}{}

	br := bridge.NewBridge(rabbit, mongodb, topo)
	server := &http.Server{Addr: ":8000", Handler: router.Router(router.Container{
		Topology:  topo,
		AppCancel: cancel,
		RabbitMq:  rabbit,
		CloseApp:  marker,
	})}

	go func() {
		// Wait for signals
		signals := make(chan os.Signal, 1)
		signal.Notify(signals, syscall.SIGINT, syscall.SIGTERM)

		_ = <-signals
		go server.Shutdown(context.Background())
		cancel()
	}()

	go server.ListenAndServe()
	log.Info().Msg("Listening on port [:8000]")
	br.Run(ctx)

	<-marker
}
