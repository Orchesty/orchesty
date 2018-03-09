package server

import (
	"fmt"
	"net"
	"golang.org/x/net/context"
	"google.golang.org/grpc"
	"google.golang.org/grpc/reflection"
	"clever-monitor.com/limiter/pkg/logger"
	"clever-monitor.com/workflow/pkg/handler"
	ws "clever-monitor.com/workflow/workflowservice"
)

const MaxInt = int(^uint(0) >> 1)

type server struct{
	addr string
	wfHandler handler.Handler
	logger logger.Logger
	requestCount int
}

func NewServer(addr string, h handler.Handler, l logger.Logger) *server {
	return &server{addr: addr, wfHandler: h, logger: l}
}

// Start prepares and runs the tcp server with grpc bindings
func (s *server) Start() {
	lis, err := net.Listen("tcp", s.addr)
	if err != nil {
		s.logger.Error("Failed to listen to grpc", logger.Context{"error": err})
	}
	grpcServer := grpc.NewServer()

	ws.RegisterWorkflowServiceServer(grpcServer, s)

	// Register reflection service on gRPC server.
	reflection.Register(grpcServer)
	if err := grpcServer.Serve(lis); err != nil {
		s.logger.Error("Failed to serve", logger.Context{"error": err})
	}
}

func (s *server) CreateWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.process(in, handler.HandleCreate)
}

func (s *server) ReadWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.process(in, handler.HandleRead)
}

func (s *server) UpdateWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.process(in, handler.HandleUpdate)
}

func (s *server) DeleteWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.process(in, handler.HandleDelete)
}

// TODO - how to implement?
func (s *server) ReadConfig(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowConfig, error) {
	s.logger.Info("ReadConfig request accepted.", logger.Context{})

	return &ws.WorkflowConfig{}, nil
}

func (s *server) process(in *ws.WorkflowRequest, method string) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId(method)
	go s.logRequest(in, reqId)

	response := s.wfHandler.Handle(method, in)

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

func (s *server) logRequest(req *ws.WorkflowRequest, reqId string) {
	s.logger.Info("Request received", logger.Context{"reqId": reqId})
}

func (s *server) logResponse(response *ws.WorkflowResponse, reqId string) {
	s.logger.Info(
		fmt.Sprintf("Sending response. Code: '%d' Message: '%s'", response.Code, response.Message),
		logger.Context{"reqId": reqId},
	)
}
