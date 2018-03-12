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
	//r, err := client.UpdateWorkflow(ctx, &ws.WorkflowRequest{Id: "5aa66025922688f0e209592c", Json: "{\"clientId\":\"trenyrkarna.cz\",\"type\":\"s_abandoned\",\"id_config\":\"aaaaaaabbbbbbbb12234\",\"filter\":{\"in_segment\":[\"frequent_shoppers\"],\"not_in_segment\":[\"recent_shoppers\"],\"priority\":10,\"filtering_variable\":\"age\"},\"steps\":[{\"condition\":\"10<x<30\",\"recommendations\":{\"recommendation_type\":\"standard\"},\"channels\":{\"email\":{\"template\":\"template_pro_mlady\",\"dynamic_fields\":\"how?\",\"send_time\":\"now\"},\"action\":[{\"action_family\":\"tags\",\"action_type\":\"add\",\"action_time\":\"now\",\"action_trigger\":\"\",\"action_subject\":\"sha256('lidimezi10a30')\"}]},\"next_flow\":{\"clientId\":\"clientId_compatible\",\"type\":\"syntetic\",\"config\":\"hash_configu\"}},{\"condition\":\"x>30\",\"recommendations\":{\"recommendation_type\":\"standard\"},\"channels\":{\"email\":{\"template\":\"template_pro_stary\",\"dynamic_fields\":\"how?\",\"send_time\":\"now\"},\"action\":[{\"action_family\":\"tags\",\"action_type\":\"add/\",\"action_time\":\"now\",\"action_trigger\":\"empty\",\"action_subject\":\"sha256('lidinad30')\"}]},\"next_flow\":{\"clientId\":\"trenyrkarna.cz\",\"type\":\"s_followup\",\"config\":\"aaaaaaabbbbbbbb333333\"}}]}"})
	//r, err := client.ReadWorkflow(ctx, &ws.WorkflowRequest{Id: "5aa228e1922688649d414d84"})
	//r, err := client.DeleteWorkflow(ctx, &ws.WorkflowRequest{Id: "5aa228e1922688649d414d84"})
	r, err := client.ReadConfig(ctx, &ws.WorkflowRequest{Id: "5aa66025922688f0e209592c"})
	if err != nil {
		log.Fatalf("grpc call error: %v", err)
	}

	//log.Printf("Response Code: %v", r.Code)
	//log.Printf("Response Message: %s", r.Message)
	log.Printf("%v", r)
}
