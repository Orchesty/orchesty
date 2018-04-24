package server

import (
	"fmt"
	"net"
	"os"
	"golang.org/x/net/context"
	"google.golang.org/grpc"
	"google.golang.org/grpc/reflection"
	"clever-monitor/utils/logger"
	"clever-monitor/workflow/pkg/handler"
	ws "clever-monitor/workflow/pkg/workflowservice"
)

const MaxInt = int(^uint(0) >> 1)

type server struct {
	addr           string
	wfHandler      handler.Handler
	logger         logger.Logger
	requestCount   int
}

// NewServer creates and returns new server struct instance
func NewServer(addr string, workflow handler.Handler, l logger.Logger) *server {
	return &server{
		addr: addr,
		wfHandler: workflow,
		logger: l,
	}
}

// Start prepares and runs the tcp server with grpc bindings
func (s *server) Start() {
	lis, err := net.Listen("tcp", s.addr)
	if err != nil {
		s.logger.Error("Failed to listen to grpc", logger.Context{"error": err})
		os.Exit(1)
	}

	s.logger.Info(fmt.Sprintf("Grpc Tcp server running on: %s", s.addr), logger.Context{})

	grpcServer := grpc.NewServer()

	s.logger.Info("Grpc server registering WorkflowService", logger.Context{})
	ws.RegisterWorkflowServiceServer(grpcServer, s)
	// Register reflection service on gRPC server.
	reflection.Register(grpcServer)
	err = grpcServer.Serve(lis)
	if err != nil {
		s.logger.Error("Failed to serve", logger.Context{"error": err})
		os.Exit(1)
	}
}


// TODO - implement logging using middleware

// CreateWorkflow creates new workflow
func (s *server) CreateWorkflow(ctx context.Context, in *ws.CreateRequest) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId("create_workflow")
	go s.logRequest(reqId)

	response := s.wfHandler.HandleCreate(in)

	go s.logResponse(response, reqId)

	return response, nil
}

// DeleteWorkflow removes existing workflow
func (s *server) DeleteWorkflow(ctx context.Context, in *ws.DeleteRequest) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId("delete_workflow")
	go s.logRequest(reqId)

	response := s.wfHandler.HandleDelete(in)

	go s.logResponse(response, reqId)

	return response, nil
}

// ReadEditorConfig returns editor config passed during create/update workflow
func (s *server) ReadEditorConfig(ctx context.Context, in *ws.ReadRequest) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId("read_config")
	go s.logRequest(reqId)

	response := s.wfHandler.HandleReadEditorConfig(in)

	go s.logResponse(response, reqId)

	return response, nil
}

// ReadWorkflowConfig returns content of existing generated workflow config
func (s *server) ReadWorkflowConfig(ctx context.Context, in *ws.ReadRequest) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId("read_workflow")
	go s.logRequest(reqId)

	response := s.wfHandler.HandleReadWorkflowConfig(in)

	go s.logResponse(response, reqId)

	return response, nil
}

// ReadAllWorkflowConfigs returns all generated workflow configs related to single editor config
func (s *server) ReadAllWorkflowConfigs(ctx context.Context, in *ws.ReadAllRequest) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId("read_workflow")
	go s.logRequest(reqId)

	response := s.wfHandler.HandleReadAllWorkflowConfigs(in)

	go s.logResponse(response, reqId)

	return response, nil
}

// getRequestId returns id to be used to pair request and response
func (s *server) getRequestId(method string) string {
	if s.requestCount > MaxInt {
		s.requestCount = 0
	}

	s.requestCount++

	return fmt.Sprintf("#%d-%s", s.requestCount, method)
}

// logRequest logs request using logger
func (s *server) logRequest(reqId string) {
	s.logger.Info("Request received", logger.Context{"reqId": reqId})
}

// logResponse logs response using logger
func (s *server) logResponse(response *ws.WorkflowResponse, reqId string) {
	s.logger.Info(
		fmt.Sprintf("Sending response. Code: '%d' Message: '%s'", response.Code, response.Message),
		logger.Context{"reqId": reqId},
	)
}

// logConfig logs WorkflowConfig response using logger
func (s *server) logConfig(config *ws.WorkflowConfig, reqId string) {
	s.logger.Info(
		fmt.Sprintf("Sending config response. Id: '%s'", config.Id),
		logger.Context{"reqId": reqId},
	)
}
