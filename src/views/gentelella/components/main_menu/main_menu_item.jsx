import React from 'react'
import MenuItemComponent from 'flusanec/src/components/menu/menu_item_component';
import {MENU_ITEM_TYPE} from 'flusanec/src/view_models/menu/menu_item'
import MainSubMenu from './main_sub_menu'

class MainMenuItem extends MenuItemComponent {
  render() {
    return (
      <li>
        <a onClick={this.makeAction.bind(this)}>
          {this._menuItem.icon ? <i className={this._menuItem.icon}></i> : ''}
          {this._menuItem.caption} <span className="fa fa-chevron-down"></span>
        </a>
        {this._menuItem.type == MENU_ITEM_TYPE.SUB_MENU && <MainSubMenu menu={this._menuItem.menu}></MainSubMenu>}
      </li>
    );
  }
}

export default MainMenuItem;