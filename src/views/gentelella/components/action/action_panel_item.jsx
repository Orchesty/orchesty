import React from 'react'
import MenuItemComponent from 'flusanec/src/components/menu/menu_item_component';

class ActionPanelItem extends MenuItemComponent {
  render() {
    return (
      <li><a href="" onClick={this.makeAction.bind(this)} title={this._menuItem.caption}><i className={this._menuItem.icon} /></a></li>
    );
  }
}

export default ActionPanelItem;