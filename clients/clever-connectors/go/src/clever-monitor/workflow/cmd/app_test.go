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

	testWorkflowMethods(t, client, ctx)

	stopTest <- true
}

func testWorkflowMethods(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context) {
	id := assertCreate(t, client, ctx, getValidJsonExample(t, "editor.json"))
	assertReadEditor(t, client, ctx, id, getValidJsonExample(t, "editor.json"))
	assertDelete(t, client, ctx, id)
	assertReadEditorFailure(t, client, ctx, id)
}

func assertCreate(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, data string) string {
	r, err := client.CreateWorkflow(ctx, &ws.CreateRequest{Json: data})
	assert.Nil(t, err)
	assert.Equal(t, int32(handler.OK), r.Code)
	assert.True(t, bson.IsObjectIdHex(r.Id))

	return r.Id
}

func assertReadEditor(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, id string, expected string) {
	r, err := client.ReadEditorConfig(ctx, &ws.ReadRequest{Id: id})
	assert.Nil(t, err)
	assert.Equal(t, int32(handler.OK), r.Code)
	assert.Equal(t, id, r.Id)
	assert.Equal(t, expected, r.Json)
}

func assertReadEditorFailure(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, id string) {
	r, err := client.ReadEditorConfig(ctx, &ws.ReadRequest{Id: id})
	assert.Nil(t, err)
	assert.Equal(t, int32(handler.NotFound), r.Code)
}

func assertDelete(t *testing.T, client ws.WorkflowServiceClient, ctx context.Context, id string) {
	r, err := client.DeleteWorkflow(ctx, &ws.DeleteRequest{Id: id})
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
