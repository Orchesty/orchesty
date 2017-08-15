import Flusanec from 'flusanec';

class SortPersistentList extends Flusanec.PersistentList{
  constructor(promise:ES6Promise, params, refreshAction = null){
    super(promise, params, refreshAction);
   // this._sort = params && params.sort;
    this._total = null;
    this._limit = null;
    this._count = null;
    this._offset = null;
  }

  get sort(){
    return this.params.sort;
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
    return this._params.requiredLimit;
  }
  
  get requiredOffset(){
    return this._params.requiredOffset;
  }

  set sort(value){
    if (!Flusanec.objectEquals(this._params.sort, value)){
      const old = this.sort;
      this._params.sort = value;
      this.refresh();
      this.emit('sort_change', this, this.sort, old);
      this.emit('params_change', this);
    }
  }

  setLimitation(limit, offset){
    if (this._params.limit != limit || this._params.offset != offset){
      this._params.limit = limit;
      this._params.offset = offset;
      if (this._params.limit != this.limit || this._params.offset != this.offset) {
        this.refresh();
      }
      this.emit('limit_change', this);
      this.emit('params_change', this);
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

  // refresh(){
  //   if (this.canRefresh()){
  //     this._refreshAction(this);
  //   }
  // }

  addLimitChangeListener(callback) {
    this.on('limit_change', callback);
  }

  removeLimitChangeListener(callback) {
    this.removeListener('limit_change', callback);
  }
}

export default SortPersistentList;