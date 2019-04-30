package notification

// Type represents the notification type
type Type string

const (
	// Null stands for undefined notification type
	Null Type = "null"

	// AccessExpiration stands for AccessExpiration notification type
	AccessExpiration Type = "access_expiration"

	// DataError stands for DataError notification type
	DataError Type = "data_error"

	// ServiceUnavailable stands for ServiceUnavailable notification type
	ServiceUnavailable Type = "service_unavailable"

	// LimitExceeded stands for LimitExceeded notification type
	LimitExceeded Type = "limit_exceeded"
)
