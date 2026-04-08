package model

import "go.mongodb.org/mongo-driver/v2/bson"

const (
	TenantID    = "tenantId"
	UserID      = "userId"
	SubjectType = "subjectType"
	SubjectID   = "subjectId"
	Channel     = "channel"
	Enabled     = "enabled"
	Filters     = "filters"
	Email       = "email"
)

type Subscription struct {
	ID          bson.ObjectID `bson:"_id,omitempty" json:"id"`
	TenantID    string        `bson:"tenantId" json:"tenant_id"`
	UserID      bson.ObjectID `bson:"userId" json:"user_id"`
	SubjectType string        `bson:"subjectType" json:"subject_type"`
	SubjectID   string        `bson:"subjectId" json:"subject_id"`
	Channel     string        `bson:"channel" json:"channel"`
	Enabled     bool          `bson:"enabled" json:"enabled"`
	Filters     *SubFilters   `bson:"filters,omitempty" json:"filters,omitempty"`
}

type SubFilters struct {
	TopologyNames []string `bson:"topologyNames,omitempty" json:"topology_names,omitempty"`
}

type User struct {
	ID    bson.ObjectID `bson:"_id"`
	Email string        `bson:"email"`
}
