package server

import (
	"net"
	"golang.org/x/net/context"
	"google.golang.org/grpc"
	"google.golang.org/grpc/reflection"
	"clever-monitor.com/limiter/pkg/logger"
	"clever-monitor.com/workflow/pkg/handler"
	ws "clever-monitor.com/workflow/workflowservice"
)

type server struct{
	addr string
	wfHandler handler.Handler
	logger logger.Logger
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
	return s.wfHandler.Handle(handler.HandleCreate, in), nil
}

func (s *server) ReadWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.wfHandler.Handle(handler.HandleRead, in), nil
}

func (s *server) UpdateWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.wfHandler.Handle(handler.HandleUpdate, in), nil
}

func (s *server) DeleteWorkflow(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	return s.wfHandler.Handle(handler.HandleDelete, in), nil
}

func (s *server) ReadConfig(ctx context.Context, in *ws.WorkflowRequest) (*ws.WorkflowConfig, error) {
	s.logger.Info("ReadConfig request accepted.", logger.Context{})

	return &ws.WorkflowConfig{}, nil
}
