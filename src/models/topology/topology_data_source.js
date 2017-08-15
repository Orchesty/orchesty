import PageDataSource from '../page_data_souce';
import Topology from './objects/topology';

class TopologyDataSource extends PageDataSource{
  constructor(httpServer: HttpServer){
    super(Topology);
    this._httpServer = httpServer;
  }
  
  _loadTopologyItems(objectAcceptCallback, list:PagePersistentList){
    let promise = this._httpServer.send('GET', '/topologies', this.listParamsToQueries(list));
    return this.promiseToPageList(promise, list, objectAcceptCallback);
  }
  
  getTopologyItems(limit, offset, sort, objectAcceptCallback){
    let list = this.initPagePersistentList(limit, offset, this._loadTopologyItems.bind(this, objectAcceptCallback), null, sort);
    list.refresh();
    return list;
  }
  
  getById(id, force = false){
    return this._establishObject(id, id => this._httpServer.send('GET', `/topologies/${id}`), force);
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