package cmd

import (
	"context"
	"github.com/hanaboso/pipes/bridge/pkg/service"
	"github.com/rs/zerolog"
	"os"
	"os/signal"
	"sync"
	"syscall"

	"github.com/spf13/cobra"
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
	`,
	Run: startBridge,
}

func startBridge(_ *cobra.Command, _ []string) {
	zerolog.TimeFieldFormat = zerolog.TimeFormatUnix

	// Construct services

	// TODO call topology.json parser here and pass max timeout to bridge

	br := service.Bridge{} // TODO Doplnit fieldy

	// Start services

	ctx, cancel := context.WithCancel(context.Background())
	wg := sync.WaitGroup{}

	wg.Add(1)
	go br.StartWaiting(&wg, ctx)

	// Wait for signals

	signals := make(chan os.Signal, 1)
	signal.Notify(signals, syscall.SIGINT, syscall.SIGTERM)

	_ = <-signals
	cancel()
	wg.Wait()
}
