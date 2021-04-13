package bridge

import (
    "github.com/hanaboso/pipes/bridge/pkg/bridge"
    "os"
    "os/exec"
    "testing"
    "time"
)

func TestShutdown(t *testing.T) {
    go process()
    go subscriber()

    // Give time to register processes
    time.Sleep(time.Millisecond * 10)
    bridge.Shutdown(0)
}

func TestShutdownTimeout(t *testing.T) {
    if os.Getenv("SHOULD_CRASH") == "1" {
        go unfinishedProcess()
        go subscriber()

        // Give time to register processes
        time.Sleep(time.Millisecond * 10)
        bridge.Shutdown(1 * time.Second)
        return
    }

    cmd := exec.Command(os.Args[0], "-test.run=TestShutdownTimeout")
    cmd.Env = append(os.Environ(), "SHOULD_CRASH=1")
    err := cmd.Run()
    if e, ok := err.(*exec.ExitError); ok && !e.Success() {
        return
    }

    t.Fatal("expected exit status 1")
}

func process() {
    defer bridge.Processing()()
    time.Sleep(time.Millisecond * 50)
}

func subscriber() {
    defer bridge.AwaitClose()()
    time.Sleep(time.Millisecond * 50)
}

func unfinishedProcess() {
    bridge.Processing()
}
