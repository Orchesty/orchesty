package notification

type Type string

const (
	Null               Type = "null"
	AccessExpiration   Type = "access_expiration"
	DataError          Type = "data_error"
	ServiceUnavailable Type = "service_unavailable"
)
