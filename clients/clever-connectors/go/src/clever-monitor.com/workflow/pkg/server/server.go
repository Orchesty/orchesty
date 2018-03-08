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
	reqId := s.getRequestId()
	s.logger.Info("CreateWorkflow request received", logger.Context{"reqId": reqId})

	result := s.wfHandler.Handle(handler.HandleCreate, in)

	s.logger.Info("CreateWorkflow sending response: " + result.Message, logger.Context{"reqId": reqId})

	return result, nil
}

func (s *server) ReadWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId()
	s.logger.Info("ReadWorkflow request received", logger.Context{"reqId": reqId})

	result := s.wfHandler.Handle(handler.HandleRead, in)

	s.logger.Info("ReadWorkflow sending response: " + result.Message, logger.Context{"reqId": reqId})

	return result, nil
}

func (s *server) UpdateWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId()
	s.logger.Info("UpdateWorkflow request received", logger.Context{"reqId": reqId})

	result := s.wfHandler.Handle(handler.HandleUpdate, in)

	s.logger.Info("UpdateWorkflow sending response: " + result.Message, logger.Context{"reqId": reqId})

	return result, nil
}

func (s *server) DeleteWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	reqId := s.getRequestId()
	s.logger.Info("DeleteWorkflow request received", logger.Context{"reqId": reqId})

	result := s.wfHandler.Handle(handler.HandleDelete, in)

	s.logger.Info("DeleteWorkflow sending response: " + result.Message, logger.Context{"reqId": reqId})

	return result, nil
}

func (s *server) ReadConfig(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowConfig, error) {
	s.logger.Info("ReadConfig request accepted.", logger.Context{})

	return &ws.WorkflowConfig{}, nil
}

// getRequestId returns id to be used to pair request and response
func (s *server) getRequestId() string {
	if s.requestCount > MaxInt {
		s.requestCount = 0
	}

	s.requestCount++

	return fmt.Sprintf("#%d", s.requestCount)
}
