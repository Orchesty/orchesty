package metrics

import (
	"context"

	"metrics-collector/pkg/storage"
)

type Collector interface {
	Collect(ctx context.Context, repo *storage.MongoRepository) error

	Name() string
}
