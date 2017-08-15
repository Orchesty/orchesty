import React from 'react'
import Flusanec from 'flusanec';

import LocalMenuItem from './local_menu_item';

class LocalMenu extends Flusanec.Component {
  _initialize(){
    super._initialize();
    this._onChange = this.onChange.bind(this);
  }

  _useProps(props){
    super._useProps(props);
    this.menu = props.menu;
  }

  _finalization(){
    this.menu = null;
  }

  set menu(menu: Menu){
    if (this._menu != menu){
      this._menu && this._menu.removeItemsChangeListener(this._onChange);
      this._menu = menu;
      this._menu && this._menu.addItemsChangeListener(this._onChange);
    }
  }
  
  onChange(){
    this.forceUpdate();
  }

  actionMade(val){
    if (typeof this.props.onAction == 'function'){
      this.props.onAction(this, val);
    }
  }
  
  render() {
    let menuItems = this._menu.items.map(menuItem => <LocalMenuItem onAction={this.actionMade.bind(this)} menuItem={menuItem} key={menuItem.unique}></LocalMenuItem>);
    return (
      <ul className="dropdown-menu">
        {menuItems}
      </ul>
    );
  }
}

export default LocalMenu;