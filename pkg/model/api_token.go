package model

const (
	Key = "key"
)

type ApiToken struct {
	Key string `bson:"key" json:"key"`
}
