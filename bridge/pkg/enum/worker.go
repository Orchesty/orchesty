package enum

type WorkerType string
type ServiceType string

const (
	WorkerType_Null     WorkerType  = "null"
	WorkerType_Http     WorkerType  = "http"
	WorkerType_Batch    WorkerType  = "batch"
	WorkerType_UserTask WorkerType  = "user"
	ServiceType_Rabbit  ServiceType = "rabbit"
	ServiceType_Memory  ServiceType = "memory"
)

func (w WorkerType) ServiceType() ServiceType {
	return ServiceType_Rabbit
}
