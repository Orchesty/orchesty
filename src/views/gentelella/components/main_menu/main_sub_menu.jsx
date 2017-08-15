import React from 'react'
import Flusanec from 'flusanec';
import MainSubMenuItem from './main_sub_menu_item'
import SelfClosableMenuComponent from 'flusanec/src/components/menu/self_closeable_menu_component';

class MainSubMenu extends SelfClosableMenuComponent {
  _initialize(){
    super._initialize();
    this._onToggle = this.onToggle.bind(this);
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
      this._menu && this._menu.removeToggleListener(this._onToggle);
      this._menu = menu;
      this._menu && this._menu.addToggleListener(this._onToggle);
    }
  }

  close(){
    this._menu.visible = false;
  }

  onToggle(){
    if (this._menu.visible){
      this.onOpen();
    }
    else{
      this.onClose();
    }
    this.forceUpdate();
  }

  render() {
    var menuItems = this._menu.items.map(menuItem => <MainSubMenuItem onAction={this._close} menuItem={menuItem} key={menuItem.unique}></MainSubMenuItem>);
    return (
      <ul ref={(self) => {this._self = self}}  className="nav child_menu" style={{display: this._menu.visible ? 'block' : 'none'}}>
        {menuItems}
      </ul>
    );
  }
}

export default MainSubMenu;