import React from 'react'
import Flusanec from 'flusanec';
import MainMenuItem from './main_menu_item';

class MainMenu extends Flusanec.Component {
  _initialize() {
    this._onToggle = this.onToggle.bind(this);
    this._onMenuChange = this.onMenuChange.bind(this);
  }

  _useProps(props) {
    this.menu = props.menu;
  }

  _finalization(){
    this.menu = null;
  }

  set menu(menu:Menu) {
    if (this._menu != menu) {
      this._menu && this._menu.removeToggleListener(this._onToggle);
      this._menu && this._menu.removeItemsChangeListener(this._onMenuChange);
      this._menu = menu;
      this._menu && this._menu.addToggleListener(this._onToggle);
      this._menu && this._menu.addItemsChangeListener(this._onMenuChange);
    }
  }

  onToggle() {
    this.forceUpdate();
  }

  onMenuChange(){
    this.forceUpdate();
  }

  render() {
    let menuItems = this._menu.items.map(menuItem => <MainMenuItem menuItem={menuItem} key={menuItem.unique}></MainMenuItem>);
    return (
      <div id="sidebar-menu" className="main_menu_side hidden-print main_menu">
        <div className="menu_section">
          <ul className="nav side-menu">
            {menuItems}
          </ul>
        </div>
      </div>
    );
  }
}

export default MainMenu;