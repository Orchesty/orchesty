/**
 * Created by Admin on 24.3.2017.
 */

import EventEmitter from 'events';

class PageManager extends EventEmitter{
  constructor(){
    super();
  }

  addAddPageListener(callback){
    this.on('add_page', callback);
  }

  removeAddPageListener(callback){
    this.removeListener('add_page', callback);
  }

  addRemovePageListener(callback){
    this.on('remove_page', callback);
  }

  removeRemovePageListener(callback){
    this.removeListener('remove_page', callback);
  }
  
}

export default PageManager;