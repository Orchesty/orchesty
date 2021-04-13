package bridge

import (
    "github.com/hanaboso/pipes/bridge/pkg/config"
    "os"
    "sync"
    "time"
)

var closed = make(chan struct{})
var watchers = sync.WaitGroup{}
const defaultTimeout = 30 * time.Second

type DoneCallback func()

/// Graceful shutdown. Each subscribed go routine via AwaitClose method is notified
func Shutdown(timeout time.Duration) {
    config.Log.Info("Stopping application...")
    close(closed)

    finished := make(chan struct{})
    go func() {
        watchers.Wait()
        close(finished)
    }()

    if timeout <= 0 {
        timeout = defaultTimeout
    }

    select {
    case <-finished:
        os.Exit(0)
    case <-time.After(timeout):
        os.Exit(1)
    }
}

/// Subscribes caller to shutdown event -> returns callback to inform that the caller ended successfully
/// Make sure to defer result and not function itself ( defer bridge.AwaitClose()() ) !! this is a blocking call !!
func AwaitClose() DoneCallback {
    watchers.Add(1)
    <- closed

    return func() {
        watchers.Done()
    }
}

/// Marks caller as active process -> in case of Shutdown, program awaits for all processes to finish
/// Make sure to defer result and not function itself ( defer bridge.Processing()() )
func Processing() DoneCallback {
    watchers.Add(1)

    return func() {
        watchers.Done()
    }
}
