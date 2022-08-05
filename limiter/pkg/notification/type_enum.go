package notification

// TypeNotification represents the notification type
type TypeNotification string

const (
	// Null stands for undefined notification type
	Null TypeNotification = "null"

	// AccessExpiration stands for AccessExpiration notification type
	AccessExpiration TypeNotification = "access_expiration"

	// DataError stands for DataError notification type
	DataError TypeNotification = "data_error"

	// ServiceUnavailable stands for ServiceUnavailable notification type
	ServiceUnavailable TypeNotification = "service_unavailable"

	// LimitExceeded stands for LimitExceeded notification type
	LimitExceeded TypeNotification = "limit_exceeded"
)
