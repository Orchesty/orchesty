class TopologyManager {
  constructor(topologyDataSource:TopologyDataSource) {
    this._topologyDataSource = topologyDataSource;
  }

  getTopologyList(sort) {
    return this._topologyDataSource.getTopologyItems({sort});
  }

  getTopology(id, force = false){
    return this._topologyDataSource.getTopology(id, force);
  }

  updateTopology(topology:Topology, data){
    return this._topologyDataSource.updateTopology(topology.id, data);
  }
  
  enableTopology(topology:Topology){
    return this._topologyDataSource.updateTopology(topology.id, {enable: true});
  }
  
  disableTopology(topology:Topology){
    return this._topologyDataSource.updateTopology(topology.id, {enable: false});
  }
  
  getScheme(id){
    return this._topologyDataSource.getScheme(id);
  }
}

export default TopologyManager;