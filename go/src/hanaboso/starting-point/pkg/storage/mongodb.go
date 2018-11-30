package storage

import (
	"context"
	"fmt"
	"github.com/mongodb/mongo-go-driver/mongo"
	"starting-point/pkg/config"
	"time"

	log "github.com/sirupsen/logrus"
)

// MongoDB represents MongoDB
var MongoDB *mongo.Database

// CreateConnection creates MongoDB connection
func CreateConnection() context.CancelFunc {
	client, err := mongo.NewClient(fmt.Sprintf("mongodb://%s/%s", config.Config.MongoDB.Hostname, config.Config.MongoDB.Database))
	if err != nil {
		log.Error(err)
	}

	innerContext, mongoDB := context.WithTimeout(context.Background(), 10*time.Second)

	err = client.Connect(innerContext)
	if err != nil {
		log.Error(err)
	}

	MongoDB = client.Database(config.Config.MongoDB.Database)

	return mongoDB
}
