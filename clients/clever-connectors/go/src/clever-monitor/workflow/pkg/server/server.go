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
	"time"
)

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

	grpcServer := grpc.NewServer(grpc.UnaryInterceptor(s.loggingInterceptor))

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

// CreateWorkflow creates new workflow
func (s *server) CreateWorkflow(ctx context.Context, in *ws.CreateRequest) (*ws.WorkflowResponse, error) {
	return s.wfHandler.HandleCreate(in), nil
}

// DeleteWorkflow removes existing workflow
func (s *server) DeleteWorkflow(ctx context.Context, in *ws.DeleteRequest) (*ws.WorkflowResponse, error) {
	return s.wfHandler.HandleDelete(in), nil
}

// ReadEditorConfig returns editor config passed during create/update workflow
func (s *server) ReadEditorConfig(ctx context.Context, in *ws.ReadRequest) (*ws.WorkflowResponse, error) {
	return s.wfHandler.HandleReadEditorConfig(in), nil
}

// ReadWorkflowConfig returns content of existing generated workflow config
func (s *server) ReadWorkflowConfig(ctx context.Context, in *ws.ReadRequest) (*ws.WorkflowResponse, error) {
	return s.wfHandler.HandleReadWorkflowConfig(in), nil
}

// ReadAllWorkflowConfigs returns all generated workflow configs related to single editor config
func (s *server) ReadAllWorkflowConfigs(ctx context.Context, in *ws.ReadAllRequest) (*ws.WorkflowResponse, error) {
	return s.wfHandler.HandleReadAllWorkflowConfigs(in), nil
}

// logging middleware function
func (s *server) loggingInterceptor(
	ctx context.Context,
	req interface{},
	info *grpc.UnaryServerInfo,
	handler grpc.UnaryHandler,
) (interface{}, error) {
	start := time.Now()

	h, err := handler(ctx, req)

	msg := fmt.Sprintf("requested:%s , duration:%s", info.FullMethod, time.Since(start))

	s.logger.Info(msg, logger.Context{"error": err})

	return h, err
}
