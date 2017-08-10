import Flusanec from 'flusanec';

class BaseObject extends Flusanec.PersistentObject{
  constructor(data, objectType){
    super(data, objectType);
  }

  get identity(){
    return this._objectType + '#' +this._id;
  }

  refreshData(data, created = false){
    this._id = data.id;
    super.refreshData(data, created);
  }

  get id(): int{
    return this._id;
  }
}

export default BaseObject;