package server

import (
	"fmt"
	"net"
	"os"
	"golang.org/x/net/context"
	"google.golang.org/grpc"
	"google.golang.org/grpc/reflection"
	"clever-monitor.com/utils/logger"
	"clever-monitor.com/workflow/pkg/handler"
	ws "clever-monitor.com/workflow/workflowservice"
)

const MaxInt = int(^uint(0) >> 1)

type server struct {
	addr           string
	wfHandler      handler.Handler
	configProvider handler.ConfigProvider
	logger         logger.Logger
	requestCount   int
}

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

func (s *server) CreateWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.processWorkflow(in, handler.HandleCreate)
}

func (s *server) ReadWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.processWorkflow(in, handler.HandleRead)
}

func (s *server) UpdateWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.processWorkflow(in, handler.HandleUpdate)
}

func (s *server) DeleteWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.processWorkflow(in, handler.HandleDelete)
}

func (s *server) ReadConfig(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowConfig, error) {
	return s.processConfig(in)
}

func (s *server) processWorkflow(in *ws.WorkflowRequest, method string) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId(method)
	go s.logRequest(in, reqId)

	response := s.wfHandler.Handle(method, in)

	go s.logResponse(response, reqId)

	return response, nil
}

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

func (s *server) logRequest(req *ws.WorkflowRequest, reqId string) {
	s.logger.Info("Request received", logger.Context{"reqId": reqId})
}

func (s *server) logResponse(response *ws.WorkflowResponse, reqId string) {
	s.logger.Info(
		fmt.Sprintf("Sending response. Code: '%d' Message: '%s'", response.Code, response.Message),
		logger.Context{"reqId": reqId},
	)
}

func (s *server) logConfig(config *ws.WorkflowConfig, reqId string) {
	s.logger.Info(
		fmt.Sprintf("Sending config response. Id: '%s'", config.IdConfig),
		logger.Context{"reqId": reqId},
	)
}
