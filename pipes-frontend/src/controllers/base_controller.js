import Flusanec from 'flusanec';

class BaseController extends Flusanec.Controller{
  constructor(contextServices){
    super();
    this._onChangeContainerType = this.onChangeContainerType.bind(this);
    this._contextServices = contextServices;
    this._contextServices.containerType.addChangeListener(this._onChangeContainerType);
    this._menuItems = null;
    this._addToMenu();
  }

  _addToMenu(){
  }

  _removeFromMenu(){
    if (this._menuItems){
      this._contextServices.menu.removeMenuItems(this._menuItems);
      this._menuItems = null;
    }
  }

  release(){
    this._contextServices.containerType.removeChangeListener(this._onChangeContainerType);
    this._removeFromMenu();
    super.release();
  }

  onChangeContainerType(){
    this._removeFromMenu();
    this._addToMenu();
  }
}

export default BaseController;