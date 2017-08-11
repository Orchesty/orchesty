import SortDataSource from '../sort_data_souce';
import Topology from './objects/topology';

class TopologyDataSource extends SortDataSource{
  constructor(httpServer: HttpServer){
    super(Topology);
    this._httpServer = httpServer;
  }
  
  getTopologyItems(params, persistentList = null){
    let promise = this._httpServer.send('GET', '/topologies', this.paramsToQueries(params));
    return this.promiseToList(promise, persistentList, persistentList => this.getTopologyItems(persistentList.params, persistentList), params);
  }
  
  getById(id, force = false){
    return this._establishObject(id, id => this._httpServer.send('GET', `/notification_types/${id}`), force);
    // return (!force && this.cacheObjectToPromise(id)) || this._httpServer.send('GET', `/topologies/${id}`).then(
    //     response => response ? this.acceptObject(response) : response
    //   );
  }

  updateTopology(id, data){
    return this._httpServer.send('PATCH', `/topologies/${id}`, null, data).then(
      response => response ? this.acceptObject(response) : response
    );
  }
  
  getScheme(id){
    return this._httpServer.downloadFile(`/topologies/${id}/scheme.bpmn`, 'application/xml');
  }

}

export default TopologyDataSource;