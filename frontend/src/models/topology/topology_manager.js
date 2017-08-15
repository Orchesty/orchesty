import Flusanec from 'flusanec';
import Topology from './objects/topology';

class TopologyManager extends Flusanec.PersistentManager{
  constructor(dataSourceContainer: Flusanec.DataSourceContainer) {
    super(dataSourceContainer);
    this._topologyDataSource = this._dataSourceContainer.getDataSourceByObjectClass(Topology);
  }

  getTopologyList(sort){
    return this._topologyDataSource.getTopologyItems(5, 0, sort);
  }

  getTopology(id, force = false){
    return this._topologyDataSource.getById(id, force);
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