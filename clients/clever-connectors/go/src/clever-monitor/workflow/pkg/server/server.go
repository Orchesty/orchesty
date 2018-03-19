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
	configProvider handler.ConfigProvider
	logger         logger.Logger
	requestCount   int
}

// NewServer creates and returns new server struct instance
func NewServer(addr string, workflow handler.Handler, config handler.ConfigProvider, l logger.Logger) *server {
	return &server{addr: addr, wfHandler: workflow, configProvider: config, logger: l}
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

// CreateWorkflow creates new workflow from request
func (s *server) CreateWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.processWorkflow(in, handler.HandleCreate)
}

// ReadWorkflow returns content of existing workflow
func (s *server) ReadWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.processWorkflow(in, handler.HandleRead)
}

// UpdateWorkflow updates the content of stored workflow
func (s *server) UpdateWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.processWorkflow(in, handler.HandleUpdate)
}

// DeleteWorkflow removes existing workflow
func (s *server) DeleteWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.processWorkflow(in, handler.HandleDelete)
}

// ReadConfig returns hydrated WorkflowConfig from stored workflow
func (s *server) ReadConfig(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowConfig, error) {
	return s.processConfig(in)
}

// processWorkflow calls Handle method and logs request and response
func (s *server) processWorkflow(in *ws.WorkflowRequest, method string) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId(method)
	go s.logRequest(in, reqId)

	response := s.wfHandler.Handle(method, in)

	go s.logResponse(response, reqId)

	return response, nil
}

// processConfig calls configProvider and logs request and response
func (s *server) processConfig(in *ws.WorkflowRequest) (*ws.WorkflowConfig, error) {
	reqId := s.getRequestId("config")
	go s.logRequest(in, reqId)

	config := s.configProvider.GetConfig(in)

	go s.logConfig(config, reqId)

	return config, nil
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
func (s *server) logRequest(req *ws.WorkflowRequest, reqId string) {
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
