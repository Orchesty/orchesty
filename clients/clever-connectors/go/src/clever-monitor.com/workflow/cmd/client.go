package main

import (
	"log"
	"time"
	"golang.org/x/net/context"
	"google.golang.org/grpc"
	"hanaboso/utils/env"
	ws "clever-monitor.com/workflow/workflowservice"
)


func main() {
	address := env.GetEnv("SERVER_HOST", "localhost") + ":" + env.GetEnv("SERVER_PORT", "50051")

	// Set up a connection to the server.
	conn, err := grpc.Dial(address, grpc.WithInsecure())
	if err != nil {
		log.Fatalf("did not connect: %v", err)
	}
	defer conn.Close()
	client := ws.NewWorkflowServiceClient(conn)

	ctx, cancel := context.WithTimeout(context.Background(), time.Second)
	defer cancel()

	log.Println("Server requested")
	//r, err := client.CreateWorkflow(ctx, &ws.WorkflowRequest{Json: "{\"foo\": \"bar\"}"})
	r, err := client.UpdateWorkflow(ctx, &ws.WorkflowRequest{Id: "5aa2517a92268877b58d621e", Json: "{\"foo\": \"baz\"}"})
	//r, err := client.ReadWorkflow(ctx, &ws.WorkflowRequest{Id: "5aa228e1922688649d414d84"})
	//r, err := client.DeleteWorkflow(ctx, &ws.WorkflowRequest{Id: "5aa228e1922688649d414d84"})
	if err != nil {
		log.Fatalf("could not create workflow: %v", err)
	}

	log.Printf("Response Code: %v", r.Code)
	log.Printf("Response Message: %s", r.Message)
}
