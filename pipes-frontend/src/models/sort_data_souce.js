import Flusanec from 'flusanec';
import SortPersistentList from './sort_persistent_list';

class SortDataSource extends Flusanec.PersistentDataSource{

  sortToQueries(sort, queries){
    if (!queries){
      queries = {};
    }
    if (sort && sort.key){
      queries['order_by'] = sort.key + (typeof sort.type == 'string' && sort.type.toUpperCase() == 'DESC' ? '-' : '+')
    }
    return queries;
  }

  paramsToQueries(params, queries){
    if (params) {
      queries = this.sortToQueries(params.sort, queries);
      if (params.limit !== undefined && params.limit !== null) {
        queries['limit'] = params.limit;
        if (params.offset !== undefined && params.offset != null) {
          queries['offset'] = params.offset;
        }
      }
    }
    return queries;
  }

  promiseToList(promise, persistentList, refresh, objectAcceptCallback, params){
    promise = promise.then(response => {
      if (response && response.items){
        response = Object.assign({}, response, {items: this.acceptObject(response.items, objectAcceptCallback)});
      }
      return response;
    });
    if (persistentList){
      persistentList.dataLoading(promise);
      return persistentList;
    }
    else{
      let list = new SortPersistentList(promise, params, refresh);
      this._registerList(list);
      return list;
    }
  }
  

}

export default SortDataSource;