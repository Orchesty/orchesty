package mongodb

import (
	"errors"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"

	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/v2/bson"
)

var errClientNotInitialized = errors.New("mongodb client is not initialized")

const MetricsDbSuffix = "-metrics"

type Client struct {
	connection *mongodb.Connection
}

type UserInfo struct {
	OK    float64 `bson:"ok" json:"ok"`
	Users []User  `bson:"users" json:"users"`
}

type User struct {
	User       string            `bson:"user" json:"user"`
	DB         string            `bson:"db" json:"db"`
	CustomData map[string]string `bson:"customData" json:"customData"`
	Roles      []map[string]any  `bson:"roles" json:"roles"`
}

func NewClient() *Client {
	return &Client{}
}

func (m *Client) Init() error {
	config.Logger.Info("Connecting to MongoDB...", map[string]interface{}{})

	m.connection = &mongodb.Connection{}
	m.connection.Connect(config.MongoDB.DSN)

	config.Logger.Info("MongoDB connected", map[string]interface{}{})
	return nil
}

func (m *Client) CreateUser(dto *models.InstanceDTO) (bson.M, error) {
	if m.connection == nil {
		return nil, errClientNotInitialized
	}

	ctx, cancel := m.connection.Context()
	defer cancel()

	cmd := bson.D{
		{Key: "createUser", Value: dto.Instance},
		{Key: "pwd", Value: dto.MongoPassword},
		{Key: "roles", Value: bson.A{
			bson.D{{Key: "role", Value: "dbOwner"}, {Key: "db", Value: dto.Instance}},
			bson.D{{Key: "role", Value: "dbOwner"}, {Key: "db", Value: dto.Instance + MetricsDbSuffix}},
		}},
		{Key: "customData", Value: bson.D{{Key: "ocInstanceDisplayName", Value: dto.InstanceDisplayName}}},
	}

	result := bson.M{}
	if err := m.connection.Database.RunCommand(ctx, cmd).Decode(&result); err != nil {
		return nil, err
	}

	return result, nil
}

func (m *Client) GetUser(userName string) (*UserInfo, error) {
	if m.connection == nil {
		return nil, errClientNotInitialized
	}

	ctx, cancel := m.connection.Context()
	defer cancel()

	result := &UserInfo{}
	if err := m.connection.Database.RunCommand(ctx, bson.M{"usersInfo": userName}).Decode(result); err != nil {
		return nil, err
	}

	return result, nil
}

func (m *Client) DeleteUser(userName string) (bson.M, error) {
	if m.connection == nil {
		return nil, errClientNotInitialized
	}

	ctx, cancel := m.connection.Context()
	defer cancel()

	result := bson.M{}
	if err := m.connection.Database.RunCommand(ctx, bson.M{"dropUser": userName}).Decode(&result); err != nil {
		return nil, err
	}

	return result, nil
}

func (m *Client) DropDatabase(dbName string) error {
	if m.connection == nil {
		return errClientNotInitialized
	}

	ctx, cancel := m.connection.Context()
	defer cancel()

	return m.connection.Database.Client().Database(dbName).Drop(ctx)
}

func (m *Client) Ping() error {
	if m.connection == nil {
		return errClientNotInitialized
	}

	ctx, cancel := m.connection.Context()
	defer cancel()

	result := bson.M{}
	return m.connection.Database.RunCommand(ctx, bson.M{"ping": 1}).Decode(&result)
}

func (m *Client) Disconnect() {
	if m.connection != nil {
		m.connection.Disconnect()
	}
}
