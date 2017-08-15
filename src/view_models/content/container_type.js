import EventEmitter from 'events';

class ContainerType extends EventEmitter{
  constructor(type){
    super();
    this._type = (type === undefined || type === null) ? this.constructor.WINDOW_AREA : type;
  }

  get type(){
    return this._type;
  }

  set type(value){
    if (this._type != value){
      const old = this._type;
      this._type = value;
      this.emit('change', this, this._type, old);
    }
  }

  switchNext(){
    this.type = this._type == ContainerType.PAGE ? ContainerType.WINDOW_AREA : ContainerType.PAGE;
  }

  addChangeListener(callback){
    this.on('change', callback);
  }

  removeChangeListener(callback){
    this.removeListener('change', callback);
  }
}

ContainerType.WINDOW_AREA = 0;
ContainerType.PAGE = 1;

export default ContainerType;