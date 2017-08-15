import Flusanec from 'flusanec';
import PagePersistentList from './page_persistent_list';

class PageDataSource extends Flusanec.PersistentDataSource{

  sortToQueries(sort, queries){
    if (!queries){
      queries = {};
    }
    if (sort && sort.key){
      queries['order_by'] = sort.key + (typeof sort.type == 'string' && sort.type.toUpperCase() == 'DESC' ? '-' : '+')
    }
    return queries;
  }

  listParamsToQueries(list: PagePersistentList, queries){
    if (list) {
      queries = this.sortToQueries(list.sort, queries);
      if (list.requiredLimit !== undefined && list.requiredLimit !== null) {
        queries['limit'] = list.requiredLimit;
        if (list.requiredOffset !== undefined && list.requiredOffset != null) {
          queries['offset'] = list.requiredOffset;
        }
      }
    }
    return queries;
  }

  promiseToPageList(promise, persistentList, objectAcceptCallback){
    promise = promise.then(response => {
      if (response && response.items){
        response = Object.assign({}, response, {items: this.acceptObject(response.items, objectAcceptCallback)});
      }
      return response;
    });
    persistentList.dataLoading(promise);
    return promise;
  }
  
  initPagePersistentList(limit, offset, refresh, filter, sort) {
    let list = new PagePersistentList(limit, offset, refresh, filter, sort);
    this._registerList(list);
    return list;
  }
  
}

export default PageDataSource;