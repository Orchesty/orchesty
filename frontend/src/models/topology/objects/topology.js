import BaseObject from '../../base_object';

class Topology extends BaseObject{
  constructor(data){
    super(data, 'Topology');
  }

  refreshData(data, created = false){
    this._name = data.name;
    this._description = data.description;
    this._enabled = data.enabled;
    super.refreshData(data, created);
  };

  get name():string{
    return this._name;
  }

  get description():string{
    return this._description;
  }

  get enabled():boolean{
    return this._enabled;
  }
}

export default Topology;