import React from 'react'
import SelfClosableMenuComponent from 'flusanec/src/components/menu/self_closeable_menu_component';
import LocalMenu from './local_menu';

class ToggleLocalMenu extends SelfClosableMenuComponent {
  _initialize() {
    super._initialize();
    this._onToggle = this.onToggle.bind(this);
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
    return (
      <div ref={self => {this._self = self}} className="open">
        {this._menu.visible && <LocalMenu menu={this._menu} onAction={this._close}></LocalMenu>}
      </div>
    );
  }
}

export default ToggleLocalMenu;