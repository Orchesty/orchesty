package server

import (
	"net"
	"golang.org/x/net/context"
	"google.golang.org/grpc"
	"google.golang.org/grpc/reflection"
	"clever-monitor.com/limiter/pkg/logger"
	ws "clever-monitor.com/workflow/workflowservice"
)


type server struct{
	logger logger.Logger
}

func (s *server) Start() {

}

func (s *server) CreateWorkflow(context.Context, *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	s.logger.Info("CreateWorkflow request accepted.", logger.Context{})

	return &ws.WorkflowResponse{Message: "Create OK"}, nil
}

func (s *server) ReadWorkflow(context.Context, *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	s.logger.Info("ReadWorkflow request accepted.", logger.Context{})

	return &ws.WorkflowResponse{Message: "Read OK"}, nil
}

func (s *server) UpdateWorkflow(context.Context, *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	s.logger.Info("UpdateWorkflow request accepted.", logger.Context{})

	return &ws.WorkflowResponse{Message: "Update OK"}, nil
}

func (s *server) DeleteWorkflow(context.Context, *ws.WorkflowRequest) (*ws.WorkflowResponse, error) {
	s.logger.Info("DeleteWorkflow request accepted.", logger.Context{})

	return &ws.WorkflowResponse{Message: "Delete OK"}, nil
}

func (s *server) ReadConfig(context.Context, *ws.WorkflowRequest) (*ws.WorkflowConfig, error) {
	s.logger.Info("ReadConfig request accepted.", logger.Context{})

	return &ws.WorkflowConfig{}, nil
}

func NewServer(addr string, l logger.Logger) *server {
	lis, err := net.Listen("tcp", addr)
	if err != nil {
		l.Error("Failed to listen to grpc", logger.Context{"error": err})
	}
	s := grpc.NewServer()

	srv := &server{logger: l}

	ws.RegisterWorkflowServiceServer(s, srv)

	// Register reflection service on gRPC server.
	reflection.Register(s)
	if err := s.Serve(lis); err != nil {
		l.Error("Failed to serve", logger.Context{"error": err})
	}

	return srv
}