import EventEmitter from 'events';
import Notification from './notification';

class NotifyService extends EventEmitter{
  constructor(delay: int){
    super();
    this._delay = delay;
    this._onNotificationRelese = this.onNotificationRelease.bind(this);
    this._items = [];
  }
  
  get delay(): int{
    return this._delay;
  }

  get items():Array<Notification>{
    return this._items.slice(0);
  }
  
  onNotificationRelease(notification){
    const index = this._items.indexOf(notification);
    if (index != -1){
      this._items.splice(index, 1);
      this.emit('remove', this, notification);
      this.emit('change', this);
    }
  }

  createNotification(type, msg){
    const notification = new Notification(type, msg, this._delay);
    notification.addReleaseListener(this._onNotificationRelese);
    this._items.unshift(notification);
    this.emit('add', this, notification);
    this.emit('change', this);
    return notification;
  }

  addChangeListener(callback){
    this.on('change', callback)
  }

  removeChangeListener(callback){
    this.removeListener('change', callback);
  }
  
  addAddListener(callback){
    this.on('add', callback)
  }

  removeAddListener(callback){
    this.removeListener('add', callback);
  }
  
  addRemoveListener(callback){
    this.on('remove', callback)
  }

  removeRemoveListener(callback){
    this.removeListener('remove', callback);
  }
}

export default NotifyService;