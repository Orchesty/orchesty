import React from 'react'
import Flusanec from 'flusanec';

import ActionPanelItem from './action_panel_item';

class ActionPanel extends Flusanec.Component {
  _initialize() {
    super._initialize();
    this._onChange = this.onChange.bind(this);
  }

  _useProps(props) {
    super._useProps(props);
    this.menu = props.menu;
  }

  _finalization() {
    this.menu = null;
    super._finalization();
  }

  set menu(menu:Menu) {
    if (this._menu != menu) {
      this._menu && this._menu.removeItemsChangeListener(this._onChange);
      this._menu = menu;
      this._menu && this._menu.addItemsChangeListener(this._onChange);
    }
  }

  onChange() {
    this.forceUpdate();
  }

  makeAction(e, item:MenuItem) {
    e.stopPropagation();
    e.preventDefault();
    if (typeof item.action == 'function'){
      item.action();
    }
  }

  render() {
    let menuItems = this._menu.items.map(menuItem => <ActionPanelItem menuItem={menuItem}></ActionPanelItem>);
    return (
      <ul className={'action-panel ' + this.props.className}>
        {menuItems}
      </ul>
    );
  }
}

export default ActionPanel;