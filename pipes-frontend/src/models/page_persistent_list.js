/**
 * Created by Admin on 15.8.2017.
 */
import Flusanec from 'flusanec';


class PagePersistentList extends Flusanec.PersistentList{
  constructor(limit, offset, refreshAction, filter:Filter, sort){
    super(refreshAction, filter, sort);
    this._total = null;
    this._limit = null;
    this._count = null;
    this._offset = null;
    this._requiredLimit = limit;
    this._requiredOffset = offset;
  }

  get total(){
    return this._total;
  }

  get limit(){
    return this._limit;
  }

  get count(){
    return this._count;
  }

  get offset(){
    return this._offset;
  }

  get requiredLimit(){
    return this._requiredLimit;
  }

  get requiredOffset(){
    return this._requiredOffset;
  }

  setLimitation(limit, offset){
    if (this._requiredLimit != limit || this._requiredOffset != offset){
      this._requiredLimit = limit;
      this._requiredOffset = offset;
      this.emit('limit_change', this);

      if (this._state != Flusanec.DATA_STATE.LOADING && (this._requiredLimit != this._limit || this._requiredOffset != this._offset)) {
        this._refreshAction(this);
      }
    }
  }

  dataLoaded(data) {
    if (data){
      this._total = data.total;
      this._limit = data.limit;
      this._count = data.count;
      this._offset = data.offset;
      super.dataLoaded(data.items);
    }
    else {
      super.dataLoaded(data);
    }
  }

  addLimitChangeListener(callback) {
    this.on('limit_change', callback);
  }

  removeLimitChangeListener(callback) {
    this.removeListener('limit_change', callback);
  }
}

export default PagePersistentList;