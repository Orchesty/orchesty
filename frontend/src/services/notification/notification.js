import Releasable from 'flusanec/src/core/releaseable';

class Notification extends Releasable{
  constructor(type, msg, delay){
    super();
    this._type = type;
    this._msg = msg;
    this._delay = delay;
    this._createTime = new Date();
    this._timeout = setTimeout(this.onTimeout.bind(this), this._delay);
  }

  get type(){
    return this._type;
  }

  get msg(){
    return this._msg;
  }

  get delay(){
    return this._delay;
  }

  get createTime(){
    return this._createTime;
  }

  onTimeout(){
    this._timeout = null;
    this.release();
  }

  release(){
    if (this._timeout){
      clearTimeout(this._timeout);
    }
    super.release();
  }
}

Notification.SUCCESS = 'success';
Notification.INFO = 'info';
Notification.WARNING = 'warning';
Notification.ERROR = 'error';

export default Notification;
