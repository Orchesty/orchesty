import React from 'react'
import MenuItemComponent from 'flusanec/src/components/menu/menu_item_component'

class MainSubMenuItem extends MenuItemComponent {
  render() {
    return (
      <li>
        <a href="#" onClick={this.makeAction.bind(this)}>
          {this._menuItem.caption}
        </a>
      </li>
    );
  }
}

export default MainSubMenuItem;