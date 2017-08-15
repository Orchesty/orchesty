import React from 'react'
import Flusanec from 'flusanec';

import ToggleLocalMenu from '../local_menu/toggle_local_menu';

class ActionButton extends Flusanec.Component {
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

  toggleMenu(e){
    e.stopPropagation();
    e.preventDefault();
    this._menu.toggle();
  }

  render() {
    const count = this._menu.items.length;
    if (count == 0) {
      return null
    }
    else if (count == 1) {
      const item = this._menu.items[0];
      return (
        <button className="btn btn-sm btn-danger" type="button" aria-expanded="true"
          onClick={e => this.makeAction(e, item)}>{item.caption}</button>
      );
    }
    else {
      return (
        <div className="btn-group">
          <button className="btn btn-sm btn-danger dropdown-toggle" type="button" aria-expanded="true" onClick={this.toggleMenu.bind(this)}>
            Actions<span className="caret" />
          </button>
          <ToggleLocalMenu menu={this._menu}></ToggleLocalMenu>
        </div>
      )
    }
  }
}

export default ActionButton;