package service

import (
	"fmt"
	"github.com/patrickmn/go-cache"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
	"strconv"
	"time"
)

// Cache represents cache
var Cache *cache.Cache

// CreateCache creates
func CreateCache() {
	expiration, _ := strconv.Atoi(config.Config.Cache.Expiration)
	cleanUp, _ := strconv.Atoi(config.Config.Cache.CleanUp)

	Cache = cache.New(time.Duration(expiration)*time.Hour, time.Duration(cleanUp)*time.Hour)
}

// FindTopologyByID finds node by ID
func FindTopologyByID(topologyID string, nodeID string) *storage.Topology {
	topologyKey := fmt.Sprintf("%s-%s", topologyID, nodeID)
	topology, found := Cache.Get(topologyKey)

	if !found {
		foundTopology := findMongoTopologyByID(topologyID, nodeID)

		if foundTopology != nil && foundTopology.Node != nil {
			Cache.Set(topologyKey, foundTopology, 0)
			addToTopologyCache(foundTopology.Name, topologyKey)
		}

		return foundTopology
	}

	return topology.(*storage.Topology)
}

// FindTopologyByName finds node by name
func FindTopologyByName(topologyName string, nodeName string) []storage.Topology {
	topologyKey := fmt.Sprintf("%s-%s", topologyName, nodeName)
	topologies, found := Cache.Get(topologyKey)

	if !found {
		foundTopologies := findMongoTopologyByName(topologyName, nodeName)

		if len(foundTopologies) > 0 {
			Cache.Set(topologyKey, foundTopologies, 0)
			addToTopologyCache(topologyName, topologyKey)
		}

		return foundTopologies
	}

	return topologies.([]storage.Topology)
}

// InvalidateCache invalidate cache by topology name
func InvalidateCache(topologyName string) int {
	topologies, found := Cache.Get(topologyName)

	if found {
		innerTopologies := topologies.([]string)

		for _, topology := range innerTopologies {
			Cache.Delete(topology)
		}

		Cache.Delete(topologyName)

		return len(innerTopologies)
	}

	return 0
}

func addToTopologyCache(topologyName string, topologyKey string) {
	topologyKeys, found := Cache.Get(topologyName)

	if !found {
		Cache.Set(topologyName, []string{topologyKey}, 0)
	} else {
		Cache.Set(topologyName, append(topologyKeys.([]string), topologyKey), 0)
	}
}
