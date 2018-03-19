package main

import (
	"fmt"
	"testing"
	"time"
	"os"
	"github.com/stretchr/testify/assert"
	"golang.org/x/net/context"
	"clever-monitor/utils/env"
	"google.golang.org/grpc"
	ws "clever-monitor/workflow/pkg/workflowservice"
	"clever-monitor/workflow/pkg/handler"
	"gopkg.in/mgo.v2/bson"
	"io/ioutil"
)

// TestMain_CRUD runs integration test
func TestMain_CRUD(t *testing.T) {
	os.Setenv("SERVER_PORT", "55505")
	os.Setenv("MONGO_DB", "test")
	os.Setenv("MONGO_COLLECTION", "workflow_test")

	stopTest := make(chan bool, 1)
	go timeoutExit(t, stopTest)
	go main()

	go func() {
		// give server some time to start
		time.Sleep(time.Millisecond * 100)
		go testGrpcMethods(t, stopTest)
	}()

	<-stopTest
}

func timeoutExit(t *testing.T, stopTest chan bool) {
	time.Sleep(time.Second * 5)
	assert.Fail(t, "Test exceeded max permitted duration limit")
	stopTest <- true
}

func testGrpcMethods(t *testing.T, stopTest chan bool) {
	client, conn, ctx := createGrpcClient(t)
	defer conn.Close()

	testWorkflowCRUDMethods(t, client, ctx)
	testConfigMethods(t, client, ctx)

	stopTest <- true
}

func testWorkflowCRUDMethods(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context) {
	id := assertCRUDCreate(t, client, ctx, getValidJsonExample(t, "example.json"))
	assertCRUDRead(t, client, ctx, id, getValidJsonExample(t, "example.json"))
	assertCRUDUpdate(t, client, ctx, id, getValidJsonExample(t, "generated.json"))
	assertCRUDRead(t, client, ctx, id, getValidJsonExample(t, "generated.json"))
	assertCRUDDelete(t, client, ctx, id)
	assertCRUDReadFailure(t, client, ctx, id)
}

func testConfigMethods(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context) {
	r, err := client.CreateWorkflow(ctx, &ws.WorkflowRequest{Json: getValidJsonExample(t, "example.json")})
	assert.Nil(t, err)

	config, err := client.ReadConfig(ctx, &ws.WorkflowRequest{Id: r.Id})
	assert.Nil(t, err)
	assert.Equal(t, "507f1f77bcf86cd799439011", config.Id)

	client.DeleteWorkflow(ctx, &ws.WorkflowRequest{Id: r.Id})
}

func assertCRUDCreate(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, data string) string {
	r, err := client.CreateWorkflow(ctx, &ws.WorkflowRequest{Json: data})
	assert.Nil(t, err)
	assert.Equal(t, int32(handler.OK), r.Code)
	assert.True(t, bson.IsObjectIdHex(r.Id))

	return r.Id
}

func assertCRUDRead(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, id string, expected string) {
	r, err := client.ReadWorkflow(ctx, &ws.WorkflowRequest{Id: id})
	assert.Nil(t, err)
	assert.Equal(t, int32(handler.OK), r.Code)
	assert.Equal(t, id, r.Id)
	assert.Equal(t, expected, r.Json)
}

func assertCRUDReadFailure(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, id string) {
	r, err := client.ReadWorkflow(ctx, &ws.WorkflowRequest{Id: id})
	assert.Nil(t, err)
	assert.Equal(t, int32(handler.NotFound), r.Code)
}

func assertCRUDUpdate(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, id string, data string) {
	r, err := client.UpdateWorkflow(ctx, &ws.WorkflowRequest{Id: id, Json: data})
	assert.Nil(t, err)
	assert.Equal(t, int32(handler.OK), r.Code)
	assert.Equal(t, id, r.Id)
}

func assertCRUDDelete(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, id string) {
	r, err := client.DeleteWorkflow(ctx, &ws.WorkflowRequest{Id: id})
	assert.Nil(t, err)
	assert.Equal(t, int32(handler.OK), r.Code)
	assert.Equal(t, id, r.Id)
}

// createGrpcClient creates grpc client to be used for testing grpc server
func createGrpcClient(t *testing.T) (ws.WorkflowServiceClient, *grpc.ClientConn, context.Context) {
	address := env.GetEnv("SERVER_HOST", "localhost") + ":" + os.Getenv("SERVER_PORT")

	// Set up a connection to the server.
	conn, err := grpc.Dial(address, grpc.WithInsecure())
	if err != nil {
		assert.FailNow(t, fmt.Sprintf("Could not create grpc connection. Error: %s", err.Error()))
	}
	client := ws.NewWorkflowServiceClient(conn)

	ctx, _ := context.WithTimeout(context.Background(), time.Hour*5)

	return client, conn, ctx
}

// getValidJsonExample returns valid json example in string
func getValidJsonExample(t *testing.T, file string) string {
	b, err := ioutil.ReadFile("../pkg/handler/examples/" + file)
	assert.Nil(t, err)

	return string(b)
}
