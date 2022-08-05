package cmd

import (
	"context"
	"github.com/hanaboso/pipes/counter/pkg/counter"
	"github.com/hanaboso/pipes/counter/pkg/mongo"
	"github.com/hanaboso/pipes/counter/pkg/rabbit"
	"github.com/rs/zerolog/log"
	"github.com/spf13/cobra"
	"os"
	"os/signal"
	"syscall"
)

func init() {
	rootCmd.AddCommand(startCmd)
}

var startCmd = &cobra.Command{
	Use:   "start",
	Short: "Starts multi-counter",
	Long: `
		APP_DEBUG: bool - enable debug log
		RABBITMQ_DSN: string
		MONGODB_DSN: string
	`,
	Run: start,
}

func start(_ *cobra.Command, _ []string) {
	log.Info().Msg("Starting multi-counter...")
	ctx, cancel := context.WithCancel(context.Background())
	rabbitMq := rabbit.NewRabbitMq()
	mongoDb := mongo.NewMongo()

	go func() {
		signals := make(chan os.Signal, 1)
		signal.Notify(signals, syscall.SIGINT, syscall.SIGTERM)

		_ = <-signals
		cancel()
	}()

	c := counter.NewMultiCounter(rabbitMq, mongoDb)
	go c.Start(ctx)

	<-ctx.Done()
	log.Info().Msg("Stopping multi-counter...")
	rabbitMq.Stop()
}
