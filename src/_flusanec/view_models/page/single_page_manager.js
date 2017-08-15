/**
 * Created by Admin on 24.3.2017.
 */

import PageManager from './page_manager';

class SinglePageManager extends PageManager{
  constructor(){
    super();
    this._page = null;
  }

  get pageList(): Array{
    return [this._page];
  }

  addPage(page){
    if (this._page != page){
      const old = this._page;
      this._page = page;
      old && this.emit('remove_page', old);
      this.emit('add_page', page);
    }
  }

  removePage(page){
    if (this._page == page){
      const old = this._page;
      this._page = null;
      this.emit('remove_page', old);
    }
  }
}

export default SinglePageManager;