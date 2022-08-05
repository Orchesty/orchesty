package service

import (
	"fmt"
	"strconv"
	"time"

	"github.com/patrickmn/go-cache"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
)

// CacheInterface represents cache interface
type CacheInterface interface {
	InitCache()
	GetCache() *cache.Cache
	InvalidateCache(topologyName string) int
	FindTopologyByID(topologyID, nodeID string) *storage.Topology
	FindTopologyByName(topologyName, nodeName string) *storage.Topology
	FindTopologyByApplication(topologyName, nodeName, token string) (*storage.Topology, *storage.Webhook)
}

// CacheDefault represents default cache implementation
type CacheDefault struct {
	cache *cache.Cache
	mongo storage.MongoInterface
}

// Cache represents cache
var Cache CacheInterface

// CreateCache creates default cache implementation
func CreateCache() {
	Cache = &CacheDefault{mongo: storage.Mongo}
	Cache.InitCache()
}

// InitCache creates cache
func (c *CacheDefault) InitCache() {
	expiration, _ := strconv.Atoi(config.Config.Cache.Expiration)
	cleanUp, _ := strconv.Atoi(config.Config.Cache.CleanUp)
	c.cache = cache.New(time.Duration(expiration)*time.Hour, time.Duration(cleanUp)*time.Hour)
}

// GetCache returns cache
func (c *CacheDefault) GetCache() *cache.Cache {
	return c.cache
}

// FindTopologyByID finds topology by ID
func (c *CacheDefault) FindTopologyByID(topologyID, nodeID string) *storage.Topology {
	topologyKey := fmt.Sprintf("%s-%s", topologyID, nodeID)
	topology, found := c.cache.Get(topologyKey)

	if !found {
		foundTopology := c.mongo.FindTopologyByID(topologyID, nodeID)

		if foundTopology != nil && foundTopology.Node != nil {
			c.cache.Set(topologyKey, foundTopology, 0)
			addToTopologyCache(foundTopology.Name, topologyKey)
		}

		return foundTopology
	}

	return topology.(*storage.Topology)
}

// FindTopologyByName finds topology by name
func (c *CacheDefault) FindTopologyByName(topologyName, nodeName string) *storage.Topology {
	topologyKey := fmt.Sprintf("%s-%s", topologyName, nodeName)
	topology, found := c.cache.Get(topologyKey)

	if !found {
		foundTopology := c.mongo.FindTopologyByName(topologyName, nodeName)

		if foundTopology != nil {
			c.cache.Set(topologyKey, foundTopology, 0)
			addToTopologyCache(topologyName, topologyKey)
		}

		return foundTopology
	}

	return topology.(*storage.Topology)
}

// FindTopologyByApplication finds node by application
func (c *CacheDefault) FindTopologyByApplication(topologyName, nodeName, token string) (*storage.Topology, *storage.Webhook) {
	topologyKey := fmt.Sprintf("%s-%s-%s", topologyName, nodeName, token)
	cacheData, found := c.cache.Get(topologyKey)

	if !found {
		foundTopology, foundWebhook := c.mongo.FindTopologyByApplication(topologyName, nodeName, token)

		if foundTopology != nil {
			c.cache.Set(topologyKey, map[string]interface{}{"topology": foundTopology, "webhook": foundWebhook}, 0)
			addToTopologyCache(topologyName, topologyKey)
		}

		return foundTopology, foundWebhook
	}

	return cacheData.(map[string]interface{})["topology"].(*storage.Topology), cacheData.(map[string]interface{})["webhook"].(*storage.Webhook)
}

// InvalidateCache invalidate cache by topology name
func (c *CacheDefault) InvalidateCache(topologyName string) int {
	topologies, found := c.cache.Get(topologyName)

	if found {
		innerTopologies := topologies.([]string)

		for _, topology := range innerTopologies {
			c.cache.Delete(topology)
		}

		c.cache.Delete(topologyName)

		return len(innerTopologies)
	}

	return 0
}

func addToTopologyCache(topologyName, topologyKey string) {
	topologyKeys, found := Cache.GetCache().Get(topologyName)

	if !found {
		Cache.GetCache().Set(topologyName, []string{topologyKey}, 0)
	} else {
		Cache.GetCache().Set(topologyName, append(topologyKeys.([]string), topologyKey), 0)
	}
}
