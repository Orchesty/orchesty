package main

import (
	"log"
	"os"
	"time"

	"golang.org/x/net/context"
	"google.golang.org/grpc"

	"hanaboso/utils/env"

	ws "clever-monitor.com/workflow/workflowservice"
)

const defaultId = "someId"

func main() {
	address := env.GetEnv("SERVER_HOST", "localhost") + ":" + env.GetEnv("SERVER_PORT", "50051")

	// Set up a connection to the server.
	conn, err := grpc.Dial(address, grpc.WithInsecure())
	if err != nil {
		log.Fatalf("did not connect: %v", err)
	}
	defer conn.Close()
	client := ws.NewWorkflowServiceClient(conn)

	// Contact the server and print out its response.
	id := defaultId
	if len(os.Args) > 1 {
		id = os.Args[1]
	}

	ctx, cancel := context.WithTimeout(context.Background(), time.Second)
	defer cancel()

	log.Println("Called CreateWorkflow:")
	r, err := client.CreateWorkflow(ctx, &ws.WorkflowRequest{Id: id})
	if err != nil {
		log.Fatalf("could not create workflow: %v", err)
	}

	log.Printf("Response Code: %v", r.Code)
	log.Printf("Response Message: %s", r.Message)
}
